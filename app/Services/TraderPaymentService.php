<?php

namespace App\Services;

use App\Models\Trader;
use App\Models\TraderPayment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TraderPaymentService
{
    public const ATTACHMENT_DISK = 'local';
    public const ATTACHMENT_DIRECTORY = 'trader-payments';
    public const DUPLICATE_WINDOW_SECONDS = 10;

    public function __construct(
        private SystemActivityLogger $activityLogger,
        private PurchaseOrderService $purchaseOrderService
    ) {
    }

    /**
     * @param  array{amount: numeric, payment_date: string, method: string, reference_number?: string|null, notes?: string|null}  $data
     */
    public function create(Trader $trader, array $data, ?UploadedFile $attachment, ?int $actorId): TraderPayment
    {
        $path = $attachment?->store(self::ATTACHMENT_DIRECTORY . '/' . $trader->id, self::ATTACHMENT_DISK);

        try {
            $payment = DB::transaction(function () use ($trader, $data, $path, $actorId) {
                Trader::query()->lockForUpdate()->findOrFail($trader->id);
                $this->assertNotDuplicate($trader, $data, $actorId);

                $payment = $trader->payments()->create(array(
                    'amount' => round((float) $data['amount'], 2),
                    'payment_date' => $data['payment_date'],
                    'method' => $data['method'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'attachment_path' => $path ?: null,
                    'status' => TraderPayment::STATUS_ACTIVE,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ));

                $payment->payment_number = $this->purchaseOrderService->formatNumber('PAY-', $payment->id);
                $payment->save();

                return $payment;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk(self::ATTACHMENT_DISK)->delete($path);
            }
            throw $e;
        }

        $this->activityLogger->log(
            'trader_payment.created',
            'trader_payment',
            $payment->id,
            $payment->payment_number,
            array(
                'trader_id' => $trader->id,
                'trader_name' => $trader->name,
                'amount' => (string) $payment->amount,
                'method' => $payment->method,
            )
        );

        return $payment;
    }

    public function void(TraderPayment $payment, string $reason, ?int $actorId): TraderPayment
    {
        $payment = DB::transaction(function () use ($payment, $reason, $actorId) {
            $payment = TraderPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->isCancelled()) {
                throw ValidationException::withMessages(array('payment' => 'This payment is already cancelled.'));
            }

            $payment->update(array(
                'status' => TraderPayment::STATUS_CANCELLED,
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => $actorId,
            ));

            return $payment;
        });

        $this->activityLogger->log(
            'trader_payment.voided',
            'trader_payment',
            $payment->id,
            $payment->payment_number,
            array('reason' => $reason, 'amount' => (string) $payment->amount, 'trader_id' => $payment->trader_id)
        );

        return $payment;
    }

    /**
     * Rejects an identical payment by the same user within the window (double submit / retried request).
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNotDuplicate(Trader $trader, array $data, ?int $actorId): void
    {
        $duplicate = $trader->payments()
            ->active()
            ->where('amount', round((float) $data['amount'], 2))
            ->whereDate('payment_date', $data['payment_date'])
            ->where('method', $data['method'])
            ->where('reference_number', $data['reference_number'] ?? null)
            ->where('created_by', $actorId)
            ->where('created_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(array(
                'amount' => 'Duplicate payment: an identical payment was just recorded for this trader.',
            ));
        }
    }

    public function attachmentExists(TraderPayment $payment): bool
    {
        return $payment->attachment_path !== null
            && Storage::disk(self::ATTACHMENT_DISK)->exists($payment->attachment_path);
    }
}
