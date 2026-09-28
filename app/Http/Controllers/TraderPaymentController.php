<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTraderPaymentRequest;
use App\Http\Requests\VoidTraderTransactionRequest;
use App\Models\Trader;
use App\Models\TraderPayment;
use App\Services\TraderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraderPaymentController extends Controller
{
    public function __construct(private TraderPaymentService $payments)
    {
    }

    public function store(StoreTraderPaymentRequest $request, Trader $trader): JsonResponse
    {
        $payment = $this->payments->create(
            $trader,
            $request->safe()->except('attachment'),
            $request->file('attachment'),
            $request->user('admin')?->id
        );

        return response()->json(array(
            'message' => "Payment {$payment->payment_number} recorded.",
            'payment' => $payment,
        ));
    }

    public function void(VoidTraderTransactionRequest $request, TraderPayment $payment): JsonResponse
    {
        $payment = $this->payments->void($payment, $request->validated('reason'), $request->user('admin')?->id);

        return response()->json(array('message' => "Payment {$payment->payment_number} voided."));
    }

    public function attachment(TraderPayment $payment): StreamedResponse
    {
        abort_unless($this->payments->attachmentExists($payment), 404);

        $extension = pathinfo($payment->attachment_path, PATHINFO_EXTENSION);

        return Storage::disk(TraderPaymentService::ATTACHMENT_DISK)->download(
            $payment->attachment_path,
            $payment->payment_number . ($extension ? '.' . $extension : '')
        );
    }
}
