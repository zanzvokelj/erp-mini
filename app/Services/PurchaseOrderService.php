<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierPayment;
use Carbon\CarbonInterface;
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

    public function createDraft(
        int $supplierId,
        ?int $warehouseId,
        float $taxRate = 0,
        ?string $poNumber = null
    ): PurchaseOrder {
        return PurchaseOrder::create([
            'po_number' => $poNumber ?? ('PO-' . now()->timestamp),
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'status' => 'draft',
            'tax_rate' => $taxRate,
        ]);
    }

    public function createDraftForProduct(
        Product $product,
        int $quantity,
        ?int $warehouseId = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($product, $quantity, $warehouseId) {
            $purchaseOrder = $this->createDraft(
                supplierId: $product->supplier_id,
                warehouseId: $warehouseId
            );

            $this->addItem(
                $purchaseOrder,
                $product->id,
                $quantity,
                (float) $product->cost_price
            );

            return $purchaseOrder->fresh();
        });
    }

    public function addItem(
        PurchaseOrder $po,
        int $productId,
        int $quantity,
        float $costPrice
    ): PurchaseOrderItem {
        if ($po->status !== 'draft') {
            throw new \Exception('Items can only be added to draft purchase orders.');
        }

        $item = $po->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'cost_price' => $costPrice,
        ]);

        $this->recalculateTotals($po);

        return $item;
    }

    public function recalculateTotals(PurchaseOrder $po): PurchaseOrder
    {
        $subtotal = (float) PurchaseOrderItem::where('purchase_order_id', $po->id)
            ->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as total')
            ->value('total');

        $tax = round($subtotal * (((float) $po->tax_rate) / 100), 2);
        $total = round($subtotal + $tax, 2);

        $po->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return $po->fresh();
    }

    public function markAsOrdered(
        PurchaseOrder $po,
        ?CarbonInterface $orderedAt = null
    ): PurchaseOrder {
        $po->update([
            'status' => 'ordered',
            'ordered_at' => $orderedAt ?? now(),
        ]);

        return $po->fresh();
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
