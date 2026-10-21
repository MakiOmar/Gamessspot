<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PosApiToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Tells POS an order it allocated for a sales order (`so-`) or POS sell (`pos-`) was undone,
 * so POS unfetches that line and staff can fetch a new account.
 */
class NotifyPosOrderUndoneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private const POS_REF_PATTERN = '/^(so|pos)-\d+$/';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(
        public string $storefrontOrderId,
        public string $lineKey,
        public int $accountsOrderId,
    ) {
        $this->afterCommit();
    }

    /** Null for orders POS did not allocate (manual, storefront `sf-`, legacy). */
    public static function forOrder(Order $order): ?self
    {
        $ref = (string) ($order->storefront_order_id ?? '');
        $lineKey = (string) ($order->storefront_line_key ?? '');
        if ($lineKey === '' || ! preg_match(self::POS_REF_PATTERN, $ref)) {
            return null;
        }

        return new self($ref, $lineKey, (int) $order->id);
    }

    public function handle(PosApiToken $token): void
    {
        try {
            $this->notify($token);
        } catch (\Throwable $e) {
            // Inline (sync) dispatch runs inside the manager request: log instead of failing the undo.
            if ($this->job === null || $this->job->getConnectionName() === 'sync') {
                $this->failed($e);

                return;
            }
            throw $e;
        }
    }

    private function notify(PosApiToken $token): void
    {
        $base = PosApiToken::baseUrl();
        $response = $this->post($base, $token->get($base));
        if ($response->status() === 401) {
            $token->forget();
            $response = $this->post($base, $token->get($base));
        }
        if (! $response->successful()) {
            throw new RuntimeException('POS order undo failed with status '.$response->status().'.');
        }
    }

    private function post(string $base, string $token): Response
    {
        $businessId = (int) config('services.pos_catalog.business_id', 1);

        return Http::acceptJson()
            ->timeout((int) config('services.pos_catalog.timeout', 20))
            ->withToken($token)
            ->post($base.'/api/accounts/orders/undone/'.$businessId, [
                'storefront_order_id' => $this->storefrontOrderId,
                'storefront_line_key' => $this->lineKey,
                'accounts_order_id' => $this->accountsOrderId,
            ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('POS order undo notification failed', [
            'storefront_order_id' => $this->storefrontOrderId,
            'line_key' => $this->lineKey,
            'accounts_order_id' => $this->accountsOrderId,
            'message' => $e->getMessage(),
        ]);
    }
}
