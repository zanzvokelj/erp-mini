<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
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

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'paid_at' => now()
        ]);

        $this->updateInvoiceStatus($invoice);

        return $payment;
    }

    protected function updateInvoiceStatus(Invoice $invoice)
    {
        $paid = (float) Payment::where('invoice_id', $invoice->id)->sum('amount');
        $total = (float) $invoice->total;

        if ($paid >= $total) {

            Invoice::where('id', $invoice->id)->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);

        } elseif ($paid > 0) {

            Invoice::where('id', $invoice->id)->update([
                'status' => 'partial'
            ]);

        } else {

            Invoice::where('id', $invoice->id)->update([
                'status' => 'draft'
            ]);

        }
    }
}
