<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Order;
use App\Models\Report;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Report actions record the acting user; report pages show sell log notes and who acted.
 */
class ReportActionsAndNotesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_active' => true, 'name' => 'Admin Actor']);
        $this->admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
    }

    private function makeOrder(string $notes = 'Customer asked for PS5'): Order
    {
        $game = Game::factory()->create();
        $account = Account::factory()->create(['game_id' => $game->id]);

        return Order::create([
            'seller_id'        => $this->admin->id,
            'store_profile_id' => StoresProfile::factory()->create()->id,
            'account_id'       => $account->id,
            'buyer_phone'      => '0107' . random_int(1000000, 9999999),
            'buyer_name'       => 'Buyer',
            'price'            => 100,
            'notes'            => $notes,
            'sold_item'        => 'ps5_secondary_stock',
        ]);
    }

    public function test_create_solve_archive_unarchive_record_the_actor(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.reports.store'), ['order_id' => $order->id, 'status' => 'has_problem', 'note' => 'Login fails'])
            ->assertCreated();
        $report = Report::where('order_id', $order->id)->firstOrFail();

        $this->actingAs($this->admin, 'admin')->postJson(route('reports.solve_problem'), ['report_id' => $report->id])->assertJsonPath('success', true);
        $this->actingAs($this->admin, 'admin')->postJson(route('reports.archive'), ['report_id' => $report->id])->assertJsonPath('success', true);
        $this->actingAs($this->admin, 'admin')->postJson(route('reports.unarchive'), ['report_id' => $report->id])->assertJsonPath('success', true);

        $actions = $report->actions()->get()->reverse()->values();
        $this->assertSame(array('created', 'solved', 'archived', 'unarchived'), $actions->pluck('action')->all());
        $this->assertSame(array($this->admin->id), $actions->pluck('user_id')->unique()->values()->all());
        $this->assertSame('archived', $actions[3]->from_status);
        $this->assertSame('solved', $actions[3]->to_status);
        $this->assertSame('unarchived', $report->fresh()->latestAction->action);
    }

    public function test_account_manager_sees_sell_log_notes_and_last_action_on_report_page(): void
    {
        $order = $this->makeOrder('Sell log note XYZ');
        $report = Report::create(['order_id' => $order->id, 'seller_id' => $this->admin->id, 'status' => 'has_problem', 'note' => 'Report note ABC']);
        $report->transitionTo('solved', 'solved', $this->admin->id);

        $managerRole = Role::firstOrCreate(['name' => 'account manager']);
        $managerRole->capabilities = app(RolePermissionService::class)->defaultMatrix()['account manager'];
        $managerRole->save();
        $manager = User::factory()->create(['is_active' => true]);
        $manager->roles()->attach($managerRole->id);

        $order->update(['seller_id' => $manager->id]);

        $this->actingAs($manager, 'admin')
            ->get(route('manager.orders.solved'))
            ->assertOk()
            ->assertSee('Sell log notes')
            ->assertSee('Reported by')
            ->assertSee('Last action');

        // Report pages filter rows through the search endpoint (the full list is paginated across all reports)
        $rows = $this->actingAs($manager, 'admin')
            ->getJson(route('manager.orders.search', ['search' => $order->buyer_phone, 'status' => 'solved']))
            ->assertOk()
            ->json('rows');

        $this->assertStringContainsString('Sell log note XYZ', $rows);
        $this->assertStringContainsString('Report note ABC', $rows);
        $this->assertStringContainsString('Solved by Admin Actor', $rows);
    }
}
