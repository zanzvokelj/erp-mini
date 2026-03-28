<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Services\InvoicePaymentService;

class PaymentApiController extends Controller
{
    public function __construct(
        protected InvoicePaymentService $invoicePaymentService
    ) {}

    public function store(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => ['required','numeric','min:0.01'],
            'payment_method' => ['nullable','string']
        ]);

        try {
            $payment = $this->invoicePaymentService->recordPayment(
                $invoice,
                (float) $validated['amount'],
                $validated['payment_method'] ?? null
            );

            return response()->json($payment);
        } catch (\DomainException $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }
    }
}
