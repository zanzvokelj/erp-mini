<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
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
    }
}
