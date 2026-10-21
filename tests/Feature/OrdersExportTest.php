<?php

namespace Tests\Feature;

use App\Exports\OrdersExport;
use App\Jobs\GenerateOrdersExportJob;
use App\Models\Account;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\DataExport;
use App\Models\Game;
use App\Models\Order;
use App\Models\Report;
use App\Models\Role;
use App\Models\StoresProfile;
use App\Models\User;
use App\Services\Exports\XlsxExportWriter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

/**
 * Sell log export is queued, honours the report status and date range, and only its owner can fetch it.
 */
class OrdersExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private StoresProfile $store;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(DataExport::DISK);
        $this->admin = $this->makeAdmin();
        $this->store = StoresProfile::factory()->create();
    }

    public function test_export_request_is_queued_with_validated_filters(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.orders.export'), [
                'searchOrder' => '',
                'status'      => 'archived',
                'start_date'  => '2025-09-01',
                'end_date'    => '2026-10-21',
                'id'          => $this->store->id,
            ])
            ->assertStatus(202)
            ->assertJsonPath('status', DataExport::STATUS_QUEUED)
            ->assertJsonPath('download_url', null);

        $export = DataExport::findOrFail($response->json('id'));
        $this->assertSame($this->admin->id, $export->user_id);
        $this->assertEquals(
            ['status' => 'archived', 'start_date' => '2025-09-01', 'end_date' => '2026-10-21', 'store_profile_id' => $this->store->id],
            $export->filters
        );
        Queue::assertPushed(GenerateOrdersExportJob::class, fn ($job) => $job->dataExportId === $export->id);
    }

    public function test_all_status_from_the_orders_page_means_no_report_filter(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.orders.export'), ['status' => 'all', 'start_date' => '2026-10-01', 'end_date' => '2026-10-21'])
            ->assertStatus(202);

        $this->assertArrayNotHasKey('status', DataExport::findOrFail($response->json('id'))->filters);
    }

    public function test_second_request_reuses_the_running_export(): void
    {
        Queue::fake();

        $first = $this->actingAs($this->admin, 'admin')->postJson(route('manager.orders.export'), ['status' => 'archived'])->json('id');
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.orders.export'), ['status' => 'solved'])
            ->assertOk()
            ->assertJsonPath('id', $first);

        Queue::assertPushed(GenerateOrdersExportJob::class, 1);
    }

    public function test_job_writes_only_matching_orders_and_owner_can_download(): void
    {
        $archivedInRange = $this->makeAccountOrder('2026-10-21 18:30:00', 'archived');
        $archivedCardInRange = $this->makeCardOrder('2025-09-01 00:05:00', 'archived');
        $this->makeAccountOrder('2025-08-31 23:59:00', 'archived');
        $this->makeAccountOrder('2026-10-10 10:00:00', 'solved');
        $this->makeAccountOrder('2026-10-10 10:00:00', null);

        $export = DataExport::create([
            'user_id' => $this->admin->id,
            'type'    => DataExport::TYPE_ORDERS,
            'status'  => DataExport::STATUS_QUEUED,
            'filters' => ['status' => 'archived', 'start_date' => '2025-09-01', 'end_date' => '2026-10-21', 'store_profile_id' => $this->store->id],
        ]);
        (new GenerateOrdersExportJob($export->id))->handle(new XlsxExportWriter());

        $export->refresh();
        $this->assertSame(DataExport::STATUS_COMPLETED, $export->status);
        $this->assertSame(2, $export->row_count);

        $rows = $this->readXlsx(Storage::disk(DataExport::DISK)->path($export->file_path));
        $this->assertSame('Buyer Phone', $rows[0][5]);
        $this->assertEqualsCanonicalizing([$archivedInRange->id, $archivedCardInRange->id], array_map('intval', array_column(array_slice($rows, 1), 0)));
        $cardRow = collect($rows)->first(fn ($row) => (int) $row[0] === $archivedCardInRange->id);
        $this->assertSame('Export PSN', $cardRow[4]);
        $this->assertSame($archivedCardInRange->buyer_phone, $cardRow[5]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('manager.orders.exports.show', $export))
            ->assertOk()
            ->assertJsonPath('status', DataExport::STATUS_COMPLETED)
            ->assertJsonPath('download_url', route('manager.orders.exports.download', $export));

        $this->actingAs($this->admin, 'admin')
            ->get(route('manager.orders.exports.download', $export))
            ->assertOk()
            ->assertDownload($export->file_name);
    }

    public function test_other_managers_cannot_poll_or_download_an_export(): void
    {
        $export = DataExport::create([
            'user_id'   => $this->admin->id,
            'type'      => DataExport::TYPE_ORDERS,
            'status'    => DataExport::STATUS_COMPLETED,
            'file_path' => 'exports/x.xlsx',
            'file_name' => 'orders.xlsx',
        ]);
        $other = $this->makeAdmin();

        $this->actingAs($other, 'admin')->getJson(route('manager.orders.exports.show', $export))->assertForbidden();
        $this->actingAs($other, 'admin')->get(route('manager.orders.exports.download', $export))->assertForbidden();
    }

    public function test_invalid_status_is_rejected(): void
    {
        Queue::fake();

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('manager.orders.export'), ['status' => 'deleted'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        Queue::assertNothingPushed();
    }

    public function test_map_keeps_phone_as_text_and_falls_back_to_card_category(): void
    {
        $order = $this->makeCardOrder('2026-10-01 12:00:00', null);
        $row = (new OrdersExport(['store_profile_id' => $this->store->id]))->query()->where('orders.id', $order->id)->firstOrFail();

        $mapped = (new OrdersExport())->map($row);

        $this->assertSame($order->buyer_phone, $mapped[5]);
        $this->assertSame('Export PSN', $mapped[4]);
        $this->assertSame('2026-10-01 12:00:00', $mapped[10]);
    }

    public function test_failure_marks_export_failed_but_redelivery_of_a_running_export_does_not(): void
    {
        $running = DataExport::create(['user_id' => $this->admin->id, 'type' => DataExport::TYPE_ORDERS, 'status' => DataExport::STATUS_PROCESSING]);
        (new GenerateOrdersExportJob($running->id))->failed(new MaxAttemptsExceededException('redelivered'));
        $this->assertSame(DataExport::STATUS_PROCESSING, $running->fresh()->status);

        $broken = DataExport::create(['user_id' => $this->admin->id, 'type' => DataExport::TYPE_ORDERS, 'status' => DataExport::STATUS_PROCESSING]);
        (new GenerateOrdersExportJob($broken->id))->failed(new \RuntimeException('disk full'));
        $this->assertSame(DataExport::STATUS_FAILED, $broken->fresh()->status);
        $this->assertSame('The export could not be generated.', $broken->fresh()->error);
    }

    public function test_prune_removes_expired_exports_and_files(): void
    {
        $disk = Storage::disk(DataExport::DISK);
        $disk->put('exports/old.xlsx', 'x');
        $disk->put('exports/new.xlsx', 'x');
        $old = DataExport::create(['user_id' => $this->admin->id, 'type' => DataExport::TYPE_ORDERS, 'status' => DataExport::STATUS_COMPLETED, 'file_path' => 'exports/old.xlsx']);
        $old->forceFill(['created_at' => now()->subHours(DataExport::RETENTION_HOURS + 1)])->save();
        $new = DataExport::create(['user_id' => $this->admin->id, 'type' => DataExport::TYPE_ORDERS, 'status' => DataExport::STATUS_COMPLETED, 'file_path' => 'exports/new.xlsx']);

        $this->artisan('exports:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($new);
        $disk->assertMissing('exports/old.xlsx');
        $disk->assertExists('exports/new.xlsx');
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);

        return $user;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readXlsx(string $path): array
    {
        $reader = new Reader();
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }
        $reader->close();

        return $rows;
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
