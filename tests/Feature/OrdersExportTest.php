<?php

namespace Tests\Feature;

use App\Exports\OrdersExport;
use App\Models\Account;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Game;
use App\Models\Order;
use App\Models\Report;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Sell log export honours the report status and date range sent by the orders page form.
 */
class OrdersExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private StoresProfile $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);
        $this->store = StoresProfile::factory()->create();
    }

    public function test_archived_export_with_date_range_returns_only_matching_orders(): void
    {
        Excel::fake();

        $archivedInRange = $this->makeAccountOrder('2026-10-21 18:30:00', 'archived');
        $archivedCardInRange = $this->makeCardOrder('2025-09-01 00:05:00', 'archived');
        $this->makeAccountOrder('2025-08-31 23:59:00', 'archived');
        $this->makeAccountOrder('2026-10-10 10:00:00', 'solved');
        $this->makeAccountOrder('2026-10-10 10:00:00', null);

        $this->actingAs($this->admin, 'admin')
            ->get(route('manager.orders.export', [
                'searchOrder' => '',
                'status'      => 'archived',
                'start_date'  => '2025-09-01',
                'end_date'    => '2026-10-21',
                'id'          => $this->store->id,
            ]))
            ->assertOk();

        Excel::assertDownloaded('orders.xlsx', function (OrdersExport $export) use ($archivedInRange, $archivedCardInRange) {
            $rows = $export->query()->get();

            return $rows->pluck('id')->sort()->values()->all() === collect([$archivedInRange->id, $archivedCardInRange->id])->sort()->values()->all()
                && $rows->firstWhere('id', $archivedCardInRange->id)->card_category_name === 'Export PSN';
        });
    }

    public function test_map_keeps_phone_as_text_and_falls_back_to_card_category(): void
    {
        $order = $this->makeCardOrder('2026-10-01 12:00:00', null);
        $row = (new OrdersExport(['store_profile_id' => $this->store->id]))->query()->where('orders.id', $order->id)->firstOrFail();

        $mapped = (new OrdersExport())->map($row);

        $this->assertSame(' ' . $order->buyer_phone, $mapped[5]);
        $this->assertSame('Export PSN', $mapped[4]);
        $this->assertSame('2026-10-01 12:00:00', $mapped[10]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        Excel::fake();

        $this->actingAs($this->admin, 'admin')
            ->get(route('manager.orders.export', ['status' => 'deleted']))
            ->assertSessionHasErrors('status');
    }

    private function makeAccountOrder(string $createdAt, ?string $reportStatus): Order
    {
        $account = Account::factory()->create(['game_id' => Game::factory()->create()->id]);

        return $this->finishOrder(['account_id' => $account->id, 'sold_item' => 'ps5_secondary_stock'], $createdAt, $reportStatus);
    }

    private function makeCardOrder(string $createdAt, ?string $reportStatus): Order
    {
        $category = CardCategory::firstOrCreate(['name' => 'Export PSN'], ['price' => 2500]);
        $card = Card::create(['code' => 'EXP-' . uniqid(), 'cost' => 1999, 'card_category_id' => $category->id, 'status' => false]);

        return $this->finishOrder(['card_id' => $card->id, 'sold_item' => 'card'], $createdAt, $reportStatus);
    }

    private function finishOrder(array $attributes, string $createdAt, ?string $reportStatus): Order
    {
        $order = Order::create($attributes + [
            'seller_id'        => $this->admin->id,
            'store_profile_id' => $this->store->id,
            'buyer_phone'      => '0107' . random_int(1000000, 9999999),
            'buyer_name'       => 'Buyer',
            'price'            => 100,
        ]);
        $order->forceFill(['created_at' => Carbon::parse($createdAt)])->save();

        if ($reportStatus !== null) {
            Report::create(['order_id' => $order->id, 'seller_id' => $this->admin->id, 'status' => $reportStatus, 'note' => 'Export test']);
        }

        return $order;
    }
}
