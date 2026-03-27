<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    protected ProductService $productService;
    protected AccountingService $accountingService;

    public function __construct(
        ProductService $productService,
        AccountingService $accountingService
    ) {
        $this->productService = $productService;
        $this->accountingService = $accountingService;
    }

    public function receive(PurchaseOrder $po)
    {
        if ($po->status !== 'ordered') {
            throw new \Exception('Only ordered purchase orders can be received.');
        }

        DB::transaction(function () use ($po) {

            $po->load('items.product');

            foreach ($po->items as $item) {

                $this->productService->adjustStock(
                    $item->product,
                    $po->warehouse_id,
                    'in',
                    $item->quantity,
                    'purchase_order',
                    $po->id
                );

            }

            $po->update([
                'status' => 'received',
                'received_at' => now()
            ]);

        });

        $this->accountingService->recordPurchaseOrderReceipt($po->fresh('items'));
    }

    public function recordSupplierPayment(
        PurchaseOrder $po,
        float $amount,
        ?string $paymentMethod = null
    ): SupplierPayment {
        if ($po->status !== 'received') {
            throw new \Exception('Supplier payments can only be recorded for received purchase orders.');
        }

        $totalPaid = (float) $po->payments()->sum('amount');
        $poTotal = (float) ($po->total ?? 0);

        if (($totalPaid + $amount) > $poTotal) {
            throw new \Exception('Payment exceeds purchase order total.');
        }

        $payment = SupplierPayment::create([
            'purchase_order_id' => $po->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);

        $this->accountingService->recordSupplierPayment($payment);

        return $payment;
    }
}
