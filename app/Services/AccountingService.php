<?php

namespace App\Services;

use App\Accounting\AccountingEntryTypes;
use App\Accounting\PostingMap;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Order;
use App\Models\SupplierPayment;

class AccountingService
{
    public function __construct(
        protected LedgerService $ledgerService
    ) {
    }

    public function recordInvoiceIssued(Invoice $invoice): JournalEntry
    {
        $invoice->loadMissing('customer');

        return $this->recordEntry(
            entryType: AccountingEntryTypes::INVOICE_ISSUED,
            referenceType: Invoice::class,
            referenceId: $invoice->id,
            description: "Invoice {$invoice->invoice_number} issued",
            postedAt: $invoice->issued_at ?? now(),
            lines: array_filter([
                [
                    'account_code' => PostingMap::ACCOUNTS_RECEIVABLE,
                    'debit' => (float) $invoice->total,
                    'credit' => 0,
                ],
                [
                    'account_code' => PostingMap::SALES_REVENUE,
                    'debit' => 0,
                    'credit' => (float) $invoice->subtotal,
                ],
                $invoice->tax > 0 ? [
                    'account_code' => PostingMap::OUTPUT_VAT_PAYABLE,
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
            entryType: AccountingEntryTypes::PAYMENT_RECEIVED,
            referenceType: Payment::class,
            referenceId: $payment->id,
            description: "Payment received for invoice {$payment->invoice->invoice_number}",
            postedAt: $payment->paid_at ?? now(),
            lines: [
                [
                    'account_code' => PostingMap::CASH,
                    'debit' => (float) $payment->amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => PostingMap::ACCOUNTS_RECEIVABLE,
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
            entryType: AccountingEntryTypes::PURCHASE_ORDER_RECEIVED,
            referenceType: PurchaseOrder::class,
            referenceId: $purchaseOrder->id,
            description: "Purchase order {$purchaseOrder->po_number} received",
            postedAt: $purchaseOrder->received_at ?? now(),
            lines: array_filter([
                [
                    'account_code' => PostingMap::INVENTORY_ASSET,
                    'debit' => $subtotal,
                    'credit' => 0,
                ],
                $tax > 0 ? [
                    'account_code' => PostingMap::INPUT_VAT_RECEIVABLE,
                    'debit' => $tax,
                    'credit' => 0,
                ] : null,
                [
                    'account_code' => PostingMap::ACCOUNTS_PAYABLE,
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
            entryType: AccountingEntryTypes::SUPPLIER_PAYMENT,
            referenceType: SupplierPayment::class,
            referenceId: $payment->id,
            description: "Supplier payment for purchase order {$payment->purchaseOrder->po_number}",
            postedAt: $payment->paid_at ?? now(),
            lines: [
                [
                    'account_code' => PostingMap::ACCOUNTS_PAYABLE,
                    'debit' => (float) $payment->amount,
                    'credit' => 0,
                ],
                [
                    'account_code' => PostingMap::CASH,
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
            entryType: AccountingEntryTypes::COST_OF_GOODS_SOLD,
            referenceType: Order::class,
            referenceId: $order->id,
            description: "COGS posted for order {$order->order_number}",
            postedAt: now(),
            lines: [
                [
                    'account_code' => PostingMap::COST_OF_GOODS_SOLD,
                    'debit' => $totalCost,
                    'credit' => 0,
                ],
                [
                    'account_code' => PostingMap::INVENTORY_ASSET,
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
        return $this->ledgerService->post(
            entryType: $entryType,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description,
            postedAt: $postedAt,
            lines: $lines,
        );
    }
}
