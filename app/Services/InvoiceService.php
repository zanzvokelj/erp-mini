<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected CompanyGuard $companyGuard
    ) {}

    public function generateFromOrder(Order $order, float $taxRate = 0): Invoice
    {
        $invoice = DB::transaction(function () use ($order, $taxRate) {
            $order = Order::query()
                ->with(['items.product', 'customer', 'invoice'])
                ->findOrFail($order->id);

            if ($order->invoice) {
                return $order->invoice;
            }

            $this->companyGuard->assertSameCompany(
                [$order, $order->customer, ...$order->items->pluck('product')->all()],
                'Invoice can only be generated from same-company order data.'
            );

            $subtotal = round((float) $order->subtotal, 2);
            $tax = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $tax, 2);
            $issuedAt = now();

            $invoice = Invoice::create([
                'company_id' => $order->company_id,
                'invoice_number' => $this->generateInvoiceNumber($order->company_id, $issuedAt),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'issued_at' => $issuedAt,
                'due_date' => $issuedAt->copy()->addDays(14)
            ]);

            foreach ($order->items as $item) {

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time,
                    'subtotal' => $item->price_at_time * $item->quantity
                ]);

            }

            return $invoice;
        });

        $this->accountingService->recordInvoiceIssued($invoice);

        return $invoice;
    }

    protected function generateInvoiceNumber(int $companyId, Carbon $issuedAt): string
    {
        $year = $issuedAt->format('Y');
        $prefix = $this->companyInvoicePrefix($companyId);
        $base = "{$prefix}-{$year}";

        $lastInvoiceNumber = Invoice::query()
            ->where('company_id', $companyId)
            ->where('invoice_number', 'like', $base . '-%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $lastSequence = 0;

        if ($lastInvoiceNumber) {
            $lastSequence = (int) Str::afterLast($lastInvoiceNumber, '-');
        }

        return sprintf('%s-%05d', $base, $lastSequence + 1);
    }

    protected function companyInvoicePrefix(int $companyId): string
    {
        $slug = (string) Company::query()
            ->whereKey($companyId)
            ->value('slug');

        $normalized = Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', $slug));

        return Str::substr($normalized !== '' ? $normalized : 'INV', 0, 4);
    }
}
