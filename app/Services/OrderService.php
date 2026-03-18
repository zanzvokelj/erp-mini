<?php



namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Events\OrderConfirmed;
use App\Models\OrderActivity;
use App\Services\InventoryService;
use App\Events\OrderShipped;
use Illuminate\Support\Str;

class OrderService
{
    protected ProductService $productService;
    protected InventoryService $inventoryService;

    public function __construct(
        ProductService $productService,
        InventoryService $inventoryService
    ) {
        $this->productService = $productService;
        $this->inventoryService = $inventoryService;
    }

    public function createDraftOrder(int $customerId, int $warehouseId): Order
    {
        return DB::transaction(function () use ($customerId, $warehouseId) {

            $orderNumber = $this->generateOrderNumber();

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'status' => 'draft'
            ]);

            $this->logActivity($order, 'created', 'Order created');

            return $order;
        });
    }

    public function addItem(Order $order, Product $product, int $quantity): OrderItem
    {
        return DB::transaction(function () use ($order, $product, $quantity) {

            // lock product row
            $product = Product::where('id', $product->id)
                ->lockForUpdate()
                ->first();

            $available = $this->inventoryService
                ->availableStock($product, $order->warehouse_id);

            if ($quantity > $available) {
                throw new \Exception(
                    "Only {$available} items available in stock."
                );
            }

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price_at_time' => $product->price,
                'cost_at_time' => $product->cost_price
            ]);

            $this->inventoryService->reserveStock(
                $product,
                $order->id,
                $quantity,
                $order->warehouse_id
            );

            $this->logActivity(
                $order,
                'item_added',
                "{$product->name} (qty {$quantity}) added"
            );

            return $item;
        });
    }

    public function confirmOrder(Order $order): void
    {
        if ($order->status !== 'draft') {
            throw new \Exception('Only draft orders can be confirmed.');
        }

        $order->load('items');

        if ($order->items->count() === 0) {
            throw new \Exception('Order must have at least one item.');
        }

        DB::transaction(function () use ($order) {


            $order->load('items');

            // 1️⃣ collect product ids
            $productIds = $order->items->pluck('product_id');

            // 2️⃣ lock ALL products
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($order->items as $item) {

                $product = $products[$item->product_id];

                $available = $this->inventoryService->availableStock(
                    $product,
                    $order->warehouse_id
                );

                $reservedForThisOrder = \App\Models\StockReservation::where('order_id', $order->id)
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->sum('quantity');

                $effectiveAvailable = $available + $reservedForThisOrder;

                if ($effectiveAvailable < $item->quantity) {
                    throw new \Exception(
                        "Insufficient stock for product {$product->name}"
                    );
                }
            }

            //  totals
            $this->calculateTotals($order);

            // update status
            $order->update([
                'status' => 'confirmed',
                'confirmed_at' => now()
            ]);

            $this->logActivity($order, 'confirmed', 'Order confirmed');
        });
    }
    public function calculateTotals(Order $order): void
    {
        $order->load('items');

        $subtotal = 0;

        foreach ($order->items as $item) {
            $subtotal += $item->price_at_time * $item->quantity;
        }

        $discountTotal = 0;

        $total = $subtotal - $discountTotal;

        $order->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $total
        ]);
    }

    public function logActivity(Order $order, string $type, string $description = null)
    {
        OrderActivity::create([
            'order_id' => $order->id,
            'type' => $type,
            'description' => $description,
            'created_by' => auth()->id()
        ]);
    }

    public function cancelOrder(Order $order): void
    {
        if (!in_array($order->status, ['draft','confirmed'])) {
            throw new \Exception('Only draft or confirmed orders can be cancelled.');
        }

        DB::transaction(function () use ($order) {

            // RELEASE RESERVATIONS
            $this->inventoryService->releaseReservation($order->id);

            $order->update([
                'status' => 'cancelled'
            ]);

            $this->logActivity(
                $order,
                'cancelled',
                'Order cancelled'
            );

        });
    }

    public function shipOrder(Order $order): void
    {
        \Log::info('SHIP ORDER CALLED ' . $order->id);
        if ($order->status !== 'confirmed') {
            throw new \Exception('Only confirmed orders can be shipped.');
        }

        if ($order->status === 'shipped') {
            throw new \Exception('Order already shipped.');
        }

        DB::transaction(function () use ($order) {

            $order->load('items.product');

            foreach ($order->items as $item) {

                $this->productService->adjustStock(
                    $item->product,
                    $order->warehouse_id,
                    'out',
                    $item->quantity,
                    'order',
                    $order->id
                );

            }

// remove reservation
            $this->inventoryService->releaseReservation($order->id);

            $order->update([
                'status' => 'shipped'
            ]);

            $this->logActivity(
                $order,
                'shipped',
                'Order shipped and inventory deducted'
            );

            \Log::info("Dispatching OrderShipped event {$order->id}");

            event(new OrderShipped($order));

        });
    }

    public function returnOrder(Order $order): void
    {
        if ($order->status !== 'completed') {
            throw new \Exception('Only completed orders can be returned.');
        }

        if ($order->status === 'returned') {
            throw new \Exception('Order already returned.');
        }

        DB::transaction(function () use ($order) {

            $order->load('items.product');

            foreach ($order->items as $item) {

                $this->productService->adjustStock(
                    $item->product,
                    $order->warehouse_id,
                    'in',
                    $item->quantity,
                    'order_return',
                    $order->id
                );

            }

            $order->update([
                'status' => 'returned'
            ]);

            $this->logActivity(
                $order,
                'returned',
                'Order returned and inventory restocked'
            );

        });
    }

    protected function generateOrderNumber(): string
    {
        $lastOrder = Order::lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $nextNumber = ($lastOrder->id ?? 0) + 1;

        return 'ORD-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
