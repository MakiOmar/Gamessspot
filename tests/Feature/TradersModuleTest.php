<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Role;
use App\Models\SystemActivityLog;
use App\Models\Trader;
use App\Models\TraderPayment;
use App\Models\TraderPurchaseOrder;
use App\Models\User;
use App\Services\PurchaseOrderService;
use App\Services\RolePermissionService;
use App\Services\TraderLedgerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TradersModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(array('name' => $roleName));
        $matrix = app(RolePermissionService::class)->defaultMatrix();
        $role->capabilities = $matrix[$roleName] ?? array();
        $role->save();

        $user = User::factory()->create();
        $user->roles()->sync(array($role->id));

        return $user->fresh()->load('roles');
    }

    protected function createOrder(Trader $trader, array $lines, string $date = '2026-09-01'): TraderPurchaseOrder
    {
        return app(PurchaseOrderService::class)->create(array(
            'trader_id' => $trader->id,
            'purchase_date' => $date,
            'notes' => null,
            'items' => $lines,
        ), null);
    }

    protected function line(Game $game, int $quantity, float $cost): array
    {
        return array('game_id' => $game->id, 'quantity' => $quantity, 'cost_per_account' => $cost);
    }

    protected function linkAccount(TraderPurchaseOrder $order, int $itemIndex = 0): Account
    {
        $item = $order->items()->orderBy('id')->get()[$itemIndex];

        return Account::factory()->forPurchaseOrderItem($item)->create();
    }

    protected function accountPayload(array $overrides = array()): array
    {
        return array_merge(array(
            'mail' => 'buyer' . uniqid() . '@example.com',
            'password' => 'secret123',
            'region' => 'US',
            'birthdate' => '1990-01-01',
            'login_code' => 'ABC123',
        ), $overrides);
    }

    protected function csvUpload(array $emails): UploadedFile
    {
        $lines = array('mail,password,region,birthdate,login_code');
        foreach ($emails as $email) {
            $lines[] = "{$email},pass123,US,1990-01-01,CODE1";
        }

        return UploadedFile::fake()->createWithContent('accounts.csv', implode("\n", $lines) . "\n");
    }

    public function test_every_trader_page_returns_ok_for_admin(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->withOpeningBalance(500)->create();
        $order = $this->createOrder($trader, array($this->line(Game::factory()->create(), 3, 100)));
        TraderPayment::factory()->create(array('trader_id' => $trader->id, 'amount' => 50));
        $this->linkAccount($order);

        $this->actingAs($admin, 'admin');

        $this->get(route('manager.traders.index'))->assertOk()->assertSee($trader->name);
        foreach (array('overview', 'purchase-orders', 'accounts', 'payments', 'statement') as $tab) {
            $this->get(route('manager.traders.show', array('trader' => $trader->id, 'tab' => $tab)))->assertOk();
        }
        $this->get(route('manager.purchase-orders.create', array('trader' => $trader->id)))->assertOk();
        $this->get(route('manager.purchase-orders.show', $order))->assertOk()->assertSee('Imported 1 / 3');
        $this->get(route('manager.purchase-orders.edit', $order))->assertOk();
        $this->getJson(route('manager.traders.lines', $trader))
            ->assertOk()
            ->assertJsonPath('purchase_orders.0.items.0.remaining', 2);
    }

    public function test_balance_uses_opening_balance_and_excludes_cancelled_records(): void
    {
        $trader = Trader::factory()->withOpeningBalance(1000)->create();
        $game = Game::factory()->create();
        $this->createOrder($trader, array($this->line($game, 10, 150)));
        $cancelled = $this->createOrder($trader, array($this->line($game, 2, 999)));
        app(PurchaseOrderService::class)->void($cancelled, 'Duplicate', null);
        TraderPayment::factory()->create(array('trader_id' => $trader->id, 'amount' => 700));
        TraderPayment::factory()->create(array('trader_id' => $trader->id, 'amount' => 300, 'status' => TraderPayment::STATUS_CANCELLED));

        $totals = app(TraderLedgerService::class)->totals($trader);

        $this->assertSame(1500.0, $totals['purchases']);
        $this->assertSame(700.0, $totals['payments']);
        $this->assertSame(1800.0, $totals['balance']);
        $this->assertSame(1, $totals['purchase_orders']);
    }

    public function test_statement_is_chronological_with_running_balance(): void
    {
        $trader = Trader::factory()->withOpeningBalance(200, '2026-08-01')->create();
        $game = Game::factory()->create();
        $this->createOrder($trader, array($this->line($game, 2, 100)), '2026-08-10');
        TraderPayment::factory()->create(array('trader_id' => $trader->id, 'amount' => 150, 'payment_date' => '2026-08-05'));
        TraderPayment::factory()->create(array('trader_id' => $trader->id, 'amount' => 999, 'payment_date' => '2026-08-06', 'status' => TraderPayment::STATUS_CANCELLED));

        $rows = app(TraderLedgerService::class)->statement($trader);

        $this->assertSame(array('opening', 'payment', 'payment', 'purchase_order'), $rows->pluck('type')->all());
        $this->assertSame(array(200.0, 50.0, 50.0, 250.0), $rows->pluck('balance')->all());
    }

    public function test_purchase_order_numbering_and_server_side_totals(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $games = Game::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'admin')->postJson(route('manager.purchase-orders.store'), array(
            'trader_id' => $trader->id,
            'purchase_date' => '2026-09-10',
            'items' => array(
                array('game_id' => $games[0]->id, 'quantity' => 3, 'cost_per_account' => 100.5, 'total_cost' => 1),
                array('game_id' => $games[1]->id, 'quantity' => 2, 'cost_per_account' => 50),
            ),
            'total_cost' => 5,
        ))->assertOk();

        $order = TraderPurchaseOrder::latest('id')->first();
        $this->assertSame('PO-' . str_pad((string) $order->id, 4, '0', STR_PAD_LEFT), $order->po_number);
        $this->assertSame('401.50', (string) $order->total_cost);
        $this->assertSame(5, $order->total_quantity);
        $response->assertJsonPath('redirect', route('manager.purchase-orders.show', $order));
        $this->assertTrue(SystemActivityLog::where('action', 'purchase_order.created')->where('subject_id', $order->id)->exists());
    }

    public function test_duplicate_game_lines_are_rejected(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $game = Game::factory()->create();

        $this->actingAs($admin, 'admin')->postJson(route('manager.purchase-orders.store'), array(
            'trader_id' => $trader->id,
            'purchase_date' => '2026-09-10',
            'items' => array($this->line($game, 1, 10), $this->line($game, 2, 10)),
        ))->assertStatus(422)->assertJsonValidationErrors('items.0.game_id');
    }

    public function test_line_with_imported_accounts_is_locked(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $game = Game::factory()->create();
        $order = $this->createOrder($trader, array($this->line($game, 5, 100)));
        $this->linkAccount($order);
        $this->linkAccount($order);
        $item = $order->items()->first();

        $payload = fn (array $line) => array(
            'trader_id' => $trader->id,
            'purchase_date' => '2026-09-01',
            'items' => array(array_merge(array('id' => $item->id), $line)),
        );

        $this->actingAs($admin, 'admin');
        $this->putJson(route('manager.purchase-orders.update', $order), $payload($this->line($game, 5, 120)))->assertStatus(422);
        $this->putJson(route('manager.purchase-orders.update', $order), $payload($this->line($game, 1, 100)))->assertStatus(422);
        $this->putJson(route('manager.purchase-orders.update', $order), $payload($this->line($game, 8, 100)))->assertOk();

        $this->assertSame(8, $item->fresh()->quantity);
        $this->assertSame('800.00', (string) $order->fresh()->total_cost);
    }

    public function test_void_is_blocked_when_accounts_are_imported(): void
    {
        $admin = $this->createUserWithRole('admin');
        $order = $this->createOrder(Trader::factory()->create(), array($this->line(Game::factory()->create(), 2, 10)));
        $this->linkAccount($order);

        $this->actingAs($admin, 'admin')
            ->postJson(route('manager.purchase-orders.void', $order), array('reason' => 'Wrong order'))
            ->assertStatus(422);

        $this->assertSame(TraderPurchaseOrder::STATUS_ACTIVE, $order->fresh()->status);
    }

    public function test_admin_can_void_empty_purchase_order_with_reason(): void
    {
        $admin = $this->createUserWithRole('admin');
        $order = $this->createOrder(Trader::factory()->create(), array($this->line(Game::factory()->create(), 2, 10)));

        $this->actingAs($admin, 'admin');
        $this->postJson(route('manager.purchase-orders.void', $order), array())->assertStatus(422);
        $this->postJson(route('manager.purchase-orders.void', $order), array('reason' => 'Typo'))->assertOk();

        $order->refresh();
        $this->assertTrue($order->isCancelled());
        $this->assertSame($admin->id, $order->cancelled_by);
        $this->assertSame('Typo', $order->cancellation_reason);
    }

    public function test_account_manager_can_manage_but_not_void(): void
    {
        $manager = $this->createUserWithRole('account manager');
        $trader = Trader::factory()->create();
        $order = $this->createOrder($trader, array($this->line(Game::factory()->create(), 1, 10)));
        $payment = TraderPayment::factory()->create(array('trader_id' => $trader->id));

        $this->actingAs($manager, 'admin');
        $this->get(route('manager.traders.show', $trader))->assertOk();
        $this->postJson(route('manager.traders.store'), array('name' => 'New Supplier', 'status' => 'active'))->assertOk();
        $this->postJson(route('manager.purchase-orders.void', $order), array('reason' => 'x x x'))->assertForbidden();
        $this->postJson(route('manager.trader-payments.void', $payment), array('reason' => 'x x x'))->assertForbidden();
    }

    public function test_roles_without_trader_capability_are_forbidden(): void
    {
        $sales = $this->createUserWithRole('sales');
        $trader = Trader::factory()->create();

        $this->actingAs($sales, 'admin');
        $this->get(route('manager.traders.index'))->assertForbidden();
        $this->get(route('manager.traders.show', $trader))->assertForbidden();
        $this->postJson(route('manager.traders.store'), array('name' => 'X', 'status' => 'active'))->assertForbidden();
        $this->getJson(route('manager.traders.lines', $trader))->assertForbidden();
    }

    public function test_payment_with_attachment_is_stored_privately_and_downloadable(): void
    {
        Storage::fake('local');
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('manager.trader-payments.store', $trader), array(
            'amount' => 250,
            'payment_date' => '2026-09-15',
            'method' => 'instapay',
            'reference_number' => 'IP-123',
            'attachment' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ), array('Accept' => 'application/json'))->assertOk();

        $payment = TraderPayment::latest('id')->first();
        $this->assertSame('PAY-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT), $payment->payment_number);
        Storage::disk('local')->assertExists($payment->attachment_path);

        $this->get(route('manager.trader-payments.attachment', $payment))->assertOk();
        $this->assertSame(250.0, app(TraderLedgerService::class)->totals($trader)['payments']);
    }

    public function test_manual_account_store_requires_a_purchase_order_line(): void
    {
        $admin = $this->createUserWithRole('admin');
        $game = Game::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson(route('manager.accounts.store'), $this->accountPayload(array('game_id' => $game->id, 'cost' => 10)))
            ->assertStatus(422)
            ->assertJsonValidationErrors('purchase_order_item_id');
    }

    public function test_manual_account_store_stamps_source_from_line_and_ignores_posted_game_and_cost(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $game = Game::factory()->create();
        $order = $this->createOrder($trader, array($this->line($game, 2, 175.25)), '2026-09-03');
        $item = $order->items()->first();

        $this->actingAs($admin, 'admin')->postJson(route('manager.accounts.store'), $this->accountPayload(array(
            'mail' => 'linked@example.com',
            'purchase_order_item_id' => $item->id,
            'game_id' => Game::factory()->create()->id,
            'cost' => 1,
            'trader_id' => 999999,
        )))->assertOk();

        $account = Account::where('mail', 'linked@example.com')->firstOrFail();
        $this->assertSame($game->id, $account->game_id);
        $this->assertSame($trader->id, $account->trader_id);
        $this->assertSame($order->id, $account->purchase_order_id);
        $this->assertSame($item->id, $account->purchase_order_item_id);
        $this->assertSame('2026-09-03', $account->purchase_date->toDateString());
        $this->assertSame('175.25', (string) $account->original_cost);
        $this->assertEquals(175.25, (float) $account->cost);
    }

    public function test_manual_account_store_is_blocked_when_line_is_full(): void
    {
        $admin = $this->createUserWithRole('admin');
        $order = $this->createOrder(Trader::factory()->create(), array($this->line(Game::factory()->create(), 1, 10)));
        $this->linkAccount($order);

        $this->actingAs($admin, 'admin')->postJson(route('manager.accounts.store'), $this->accountPayload(array(
            'purchase_order_item_id' => $order->items()->first()->id,
        )))->assertStatus(422)->assertJsonValidationErrors('purchase_order_item_id');
    }

    public function test_bulk_import_stamps_source_on_every_row(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $game = Game::factory()->create();
        $order = $this->createOrder($trader, array($this->line($game, 5, 80)));
        $item = $order->items()->first();
        $emails = array('imp1' . uniqid() . '@example.com', 'imp2' . uniqid() . '@example.com', 'imp3' . uniqid() . '@example.com');

        $this->actingAs($admin, 'admin')->post(route('manager.accounts.import'), array(
            'purchase_order_item_id' => $item->id,
            'file' => $this->csvUpload($emails),
        ), array('Accept' => 'application/json'))
            ->assertOk()
            ->assertJsonPath('imported', 3)
            ->assertJsonPath('line_imported', 3);

        $accounts = Account::whereIn('mail', $emails)->get();
        $this->assertCount(3, $accounts);
        foreach ($accounts as $account) {
            $this->assertSame($game->id, $account->game_id);
            $this->assertSame($trader->id, $account->trader_id);
            $this->assertSame($order->id, $account->purchase_order_id);
            $this->assertSame($item->id, $account->purchase_order_item_id);
            $this->assertSame('80.00', (string) $account->original_cost);
        }
    }

    public function test_bulk_import_is_blocked_when_rows_exceed_remaining_quantity(): void
    {
        $admin = $this->createUserWithRole('admin');
        $order = $this->createOrder(Trader::factory()->create(), array($this->line(Game::factory()->create(), 3, 80)));
        $this->linkAccount($order);
        $emails = array('over1' . uniqid() . '@example.com', 'over2' . uniqid() . '@example.com', 'over3' . uniqid() . '@example.com');

        $this->actingAs($admin, 'admin')->post(route('manager.accounts.import'), array(
            'purchase_order_item_id' => $order->items()->first()->id,
            'file' => $this->csvUpload($emails),
        ), array('Accept' => 'application/json'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertSame(0, Account::whereIn('mail', $emails)->count());
        $this->assertStringContainsString('2 remaining', json_encode(
            $this->post(route('manager.accounts.import'), array(
                'purchase_order_item_id' => $order->items()->first()->id,
                'file' => $this->csvUpload($emails),
            ), array('Accept' => 'application/json'))->json('errors.file')
        ));
    }

    public function test_account_update_cannot_change_source_game_or_cost_of_linked_account(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $game = Game::factory()->create();
        $order = $this->createOrder($trader, array($this->line($game, 2, 60)));
        $account = $this->linkAccount($order);

        $this->actingAs($admin, 'admin')->putJson(route('manager.accounts.update', $account->id), $this->accountPayload(array(
            'mail' => $account->mail,
            'game_id' => Game::factory()->create()->id,
            'cost' => 1,
            'trader_id' => Trader::factory()->create()->id,
            'purchase_order_id' => null,
            'original_cost' => 5,
            'region' => 'EU',
        )))->assertOk();

        $account->refresh();
        $this->assertSame('EU', $account->region);
        $this->assertSame($game->id, $account->game_id);
        $this->assertEquals(60, (float) $account->cost);
        $this->assertSame($trader->id, $account->trader_id);
        $this->assertSame($order->id, $account->purchase_order_id);
        $this->assertSame('60.00', (string) $account->original_cost);
    }

    public function test_legacy_account_update_can_still_change_game_and_cost(): void
    {
        $admin = $this->createUserWithRole('admin');
        $game = Game::factory()->create();
        $account = Account::factory()->create(array('game_id' => Game::factory()->create()->id, 'cost' => 10));

        $this->actingAs($admin, 'admin')->putJson(route('manager.accounts.update', $account->id), $this->accountPayload(array(
            'mail' => $account->mail,
            'game_id' => $game->id,
            'cost' => 25,
            'trader_id' => Trader::factory()->create()->id,
        )))->assertOk();

        $account->refresh();
        $this->assertSame($game->id, $account->game_id);
        $this->assertEquals(25, (float) $account->cost);
        $this->assertNull($account->trader_id);
    }

    public function test_deleting_linked_account_logs_trader_and_purchase_order(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();
        $order = $this->createOrder($trader, array($this->line(Game::factory()->create(), 1, 42)));
        $account = $this->linkAccount($order);

        $this->actingAs($admin, 'admin')->deleteJson(route('manager.accounts.destroy', $account->id))->assertOk();

        $log = SystemActivityLog::where('action', 'account.deleted')->where('subject_id', $account->id)->latest('id')->firstOrFail();
        $this->assertSame($trader->name, $log->meta['trader_name']);
        $this->assertSame($order->po_number, $log->meta['po_number']);
        $this->assertSame('42.00', $log->meta['original_cost']);
    }

    public function test_accounts_page_and_search_show_clickable_source(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create(array('name' => 'Source Trader ' . uniqid()));
        $order = $this->createOrder($trader, array($this->line(Game::factory()->create(), 1, 42)));
        $account = $this->linkAccount($order);

        $this->actingAs($admin, 'admin')->get(route('manager.accounts'))->assertOk();

        $rows = $this->getJson(route('manager.accounts.search', array('search' => $account->mail)))->assertOk()->json('rows');
        $this->assertStringContainsString(route('manager.traders.show', $trader->id), $rows);
        $this->assertStringContainsString(route('manager.purchase-orders.show', $order->id), $rows);
        $this->assertStringContainsString($order->po_number, $rows);
    }

    public function test_game_used_in_purchase_order_cannot_be_deleted(): void
    {
        $admin = $this->createUserWithRole('admin');
        $game = Game::factory()->create();
        $this->createOrder(Trader::factory()->create(), array($this->line($game, 1, 10)));

        $this->actingAs($admin, 'admin')->deleteJson(route('manager.games.destroy', $game->id))->assertStatus(422);
        $this->assertNotNull($game->fresh());
    }

    public function test_opening_balance_update_changes_balance(): void
    {
        $admin = $this->createUserWithRole('admin');
        $trader = Trader::factory()->create();

        $this->actingAs($admin, 'admin')->putJson(route('manager.traders.opening-balance', $trader), array(
            'opening_balance' => 1234.5,
            'opening_balance_date' => '2026-01-01',
        ))->assertOk();

        $this->assertSame(1234.5, app(TraderLedgerService::class)->totals($trader->fresh())['balance']);
    }
}
