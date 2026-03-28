<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OrderActivity;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePaymentService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected OrderService $orderService
    ) {}

    public function recordPayment(
        Invoice $invoice,
        float $amount,
        ?string $paymentMethod = null
    ): Payment {
        try {
            DB::beginTransaction();

            Log::info('PAYMENT START', [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $paymentMethod,
            ]);

            $invoice = Invoice::query()
                ->with(['payments', 'order'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $totalPaid = (float) $invoice->payments->sum('amount');

            Log::info('PAYMENT STATE', [
                'total' => $invoice->total,
                'already_paid' => $totalPaid,
            ]);

            if (($totalPaid + $amount) > (float) $invoice->total) {
                throw new \DomainException('Payment exceeds invoice total');
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
            ]);

            Log::info('PAYMENT CREATED', [
                'payment_id' => $payment->id,
            ]);

            $newTotalPaid = $totalPaid + $amount;
            $remaining = (float) $invoice->total - $newTotalPaid;

            OrderActivity::create([
                'order_id' => $invoice->order_id,
                'type' => 'payment_recorded',
                'description' => 'Payment of EUR ' . number_format($payment->amount, 2)
                    . ' recorded. Remaining: EUR ' . number_format($remaining, 2),
                'created_by' => auth()->id(),
            ]);

            $this->syncInvoiceStatus($invoice->fresh(['payments', 'order']));
            $this->accountingService->recordPaymentReceived($payment);

            DB::commit();

            return $payment->fresh();

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('PAYMENT FAILED ❌', [
                'message' => $e->getMessage(),
                'invoice_id' => $invoice->id ?? null,
                'amount' => $amount,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function syncInvoiceStatus(Invoice $invoice): Invoice
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total;

        if ($paid >= $total) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            if ($invoice->order && $invoice->order->status !== 'completed') {
                $this->orderService->completeOrder($invoice->order);

                OrderActivity::create([
                    'order_id' => $invoice->order_id,
                    'type' => 'order_completed',
                    'description' => 'Order automatically completed (fully paid)',
                    'created_by' => auth()->id(),
                ]);
            }

            return $invoice->fresh();
        }

        if ($paid > 0) {
            $invoice->update([
                'status' => 'partial',
                'paid_at' => null,
            ]);

            return $invoice->fresh();
        }

        $invoice->update([
            'status' => 'draft',
            'paid_at' => null,
        ]);

        return $invoice->fresh();
    }
}
