<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OrderActivity;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class InvoicePaymentService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected OrderService $orderService,
        protected CompanyGuard $companyGuard
    ) {}

    public function recordPayment(
        Invoice $invoice,
        float $amount,
        ?string $paymentMethod = null
    ): Payment {
        return DB::transaction(function () use ($invoice, $amount, $paymentMethod) {
            $invoice = Invoice::query()
                ->with(['payments', 'order'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $this->companyGuard->assertSameCompany(
                [$invoice, $invoice->order],
                'Invoice and order must belong to the same company.'
            );

            $totalPaid = (float) $invoice->payments->sum('amount');

            if (($totalPaid + $amount) > (float) $invoice->total) {
                throw new \DomainException('Payment exceeds invoice total');
            }

            $payment = Payment::create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
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

            return $payment->fresh();
        });
    }

    public function syncInvoiceStatus(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('order');

        $this->companyGuard->assertSameCompany(
            [$invoice, $invoice->order],
            'Invoice and order must belong to the same company.'
        );

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
