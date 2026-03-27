<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Order;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function __construct(
        protected AccountingPeriodService $accountingPeriodService
    ) {
    }

    public function recordInvoiceIssued(Invoice $invoice): JournalEntry
    {
        $invoice->loadMissing('customer');

        return $this->recordEntry(
            entryType: 'invoice_issued',
            referenceType: Invoice::class,
            referenceId: $invoice->id,
            description: "Invoice {$invoice->invoice_number} issued",
            postedAt: $invoice->issued_at ?? now(),
            lines: array_filter([
                [
                    'account_code' => '1100',
                    'debit' => (float) $invoice->total,
                    'credit' => 0,
                ],
                [
                    'account_code' => '4000',
                    'debit' => 0,
                    'credit' => (float) $invoice->subtotal,
                ],
                $invoice->tax > 0 ? [
                    'account_code' => '2100',
                    'debit' => 0,
                    'credit' => (float) $invoice->tax,
                ] : null,
            ])
        );
    }

    public function recordPaymentReceived(Payment $payment): JournalEntry
    {
        $payment->loadMissing('invoice');

        return $this->recordEntry(
            entryType: 'payment_received',
            referenceType: Payment::class,
            referenceId: $payment->id,
            description: "Payment received for invoice {$payment->invoice->invoice_number}",
            postedAt: $payment->paid_at ?? now(),
            lines: [
                [
                    'account_code' => '1000',
                    'debit' => (float) $payment->amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => '1100',
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                ],
            ]
        );
    }

    public function recordPurchaseOrderReceipt(PurchaseOrder $purchaseOrder): JournalEntry
    {
        $purchaseOrder->loadMissing('items');

        $subtotal = (float) $purchaseOrder->items->sum(
            fn ($item) => $item->quantity * $item->cost_price
        );
        $tax = round((float) ($purchaseOrder->tax ?? 0), 2);
        $total = round($subtotal + $tax, 2);

        return $this->recordEntry(
            entryType: 'purchase_order_received',
            referenceType: PurchaseOrder::class,
            referenceId: $purchaseOrder->id,
            description: "Purchase order {$purchaseOrder->po_number} received",
            postedAt: $purchaseOrder->received_at ?? now(),
            lines: array_filter([
                [
                    'account_code' => '1200',
                    'debit' => $subtotal,
                    'credit' => 0,
                ],
                $tax > 0 ? [
                    'account_code' => '1300',
                    'debit' => $tax,
                    'credit' => 0,
                ] : null,
                [
                    'account_code' => '2000',
                    'debit' => 0,
                    'credit' => $total,
                ],
            ])
        );
    }

    public function recordSupplierPayment(SupplierPayment $payment): JournalEntry
    {
        $payment->loadMissing('purchaseOrder');

        return $this->recordEntry(
            entryType: 'supplier_payment',
            referenceType: SupplierPayment::class,
            referenceId: $payment->id,
            description: "Supplier payment for purchase order {$payment->purchaseOrder->po_number}",
            postedAt: $payment->paid_at ?? now(),
            lines: [
                [
                    'account_code' => '2000',
                    'debit' => (float) $payment->amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => '1000',
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                ],
            ]
        );
    }

    public function recordCostOfGoodsSold(Order $order): JournalEntry
    {
        $order->loadMissing('items');

        $totalCost = (float) $order->items->sum(
            fn ($item) => $item->quantity * $item->cost_at_time
        );

        return $this->recordEntry(
            entryType: 'cost_of_goods_sold',
            referenceType: Order::class,
            referenceId: $order->id,
            description: "COGS posted for order {$order->order_number}",
            postedAt: now(),
            lines: [
                [
                    'account_code' => '5000',
                    'debit' => $totalCost,
                    'credit' => 0,
                ],
                [
                    'account_code' => '1200',
                    'debit' => 0,
                    'credit' => $totalCost,
                ],
            ]
        );
    }

    protected function recordEntry(
        string $entryType,
        string $referenceType,
        int $referenceId,
        string $description,
        $postedAt,
        array $lines
    ): JournalEntry {
        return DB::transaction(function () use (
            $entryType,
            $referenceType,
            $referenceId,
            $description,
            $postedAt,
            $lines
        ) {
            $existing = JournalEntry::where('entry_type', $entryType)
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->first();

            if ($existing) {
                return $existing->load('lines.account');
            }

            $postingDate = Carbon::parse($postedAt);
            $this->accountingPeriodService->assertPostingAllowed($postingDate);

            $entry = JournalEntry::create([
                'entry_number' => $this->generateEntryNumber(),
                'entry_type' => $entryType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'posted_at' => $postingDate,
            ]);

            $lineNumber = 1;
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $account = $this->findAccount($line['account_code']);

                $debit = round((float) $line['debit'], 2);
                $credit = round((float) $line['credit'], 2);

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'line_number' => $lineNumber++,
                ]);

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \RuntimeException('Journal entry is not balanced.');
            }

            return $entry->load('lines.account');
        });
    }

    protected function findAccount(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    protected function generateEntryNumber(): string
    {
        $last = JournalEntry::orderByDesc('id')->value('id') ?? 0;

        return 'JE-' . str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }
}
