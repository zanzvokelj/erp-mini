<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderActivity;
use Illuminate\Http\Request;
use App\Services\AccountingService;

class PaymentApiController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => ['required','numeric','min:0.01'],
            'payment_method' => ['nullable','string']
        ]);

        // 🔒 prevent overpayment
        $totalPaid = (float) $invoice->payments()->sum('amount');

        if (($totalPaid + $request->amount) > $invoice->total) {
            return response()->json([
                'error' => 'Payment exceeds invoice total'
            ], 422);
        }

        // ✅ CREATE PAYMENT
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'paid_at' => now()
        ]);

        // 🔥 PAYMENT ACTIVITY LOG
        $newTotalPaid = $totalPaid + $request->amount;
        $remaining = $invoice->total - $newTotalPaid;

        OrderActivity::create([
            'order_id' => $invoice->order_id,
            'type' => 'payment_recorded',
            'description' =>
                'Payment of €' . number_format($payment->amount, 2) .
                ' recorded. Remaining: €' . number_format($remaining, 2),
            'created_by' => auth()->id(),
        ]);

        // 🔄 UPDATE STATUS
        $this->updateInvoiceStatus($invoice);

        $this->accountingService->recordPaymentReceived($payment);

        return response()->json($payment);
    }

    protected function updateInvoiceStatus(Invoice $invoice)
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total;

        if ($paid >= $total) {

            // ✅ INVOICE PAID
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);

            // 🔥 AUTO COMPLETE ORDER
            $invoice->order->update([
                'status' => 'completed'
            ]);

            OrderActivity::create([
                'order_id' => $invoice->order_id,
                'type' => 'order_completed',
                'description' => 'Order automatically completed (fully paid)',
                'created_by' => auth()->id(),
            ]);

        } elseif ($paid > 0) {

            // 🟡 PARTIAL
            $invoice->update([
                'status' => 'partial'
            ]);

        } else {

            // ⚪ DRAFT
            $invoice->update([
                'status' => 'draft'
            ]);
        }
    }
}
