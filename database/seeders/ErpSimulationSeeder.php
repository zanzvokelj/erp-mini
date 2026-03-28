<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\InvoiceService;
use App\Services\InvoicePaymentService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;

class ErpSimulationSeeder extends Seeder
{
    private const SIMULATION_START = '2026-01-01 08:00:00';
    private const SIMULATION_END = '2026-03-27 18:00:00';
    private const SUPPLIER_COUNT = 18;
    private const CUSTOMER_COUNT = 240;
    private const PRODUCT_COUNT = 144;
    private const PURCHASE_ORDER_COUNT = 110;
    private const TRANSFER_COUNT = 90;
    private const SALES_ORDER_COUNT = 1250;

    protected Collection $suppliers;
    protected Collection $customers;
    protected Collection $products;
    protected Collection $warehouses;
    protected Warehouse $mainWarehouse;
    protected Warehouse $secondaryWarehouse;
    protected Collection $secondaryAssortment;
    protected Collection $topSellers;
    protected Collection $productsBySupplier;
    protected array $weightedPoolByWarehouse = [];

    public function run(): void
    {
        Queue::fake();

        $this->seedCoreData();
        $this->buildCatalogSegments();
        $this->seedOpeningStock();
        $this->seedPurchaseFlow();
        $this->seedWarehouseTransfers();
        $this->seedSalesFlow();
        $this->rebalanceFinanceDashboard();

        Carbon::setTestNow();
    }

    protected function simulationStart(): Carbon
    {
        return Carbon::parse(self::SIMULATION_START);
    }

    protected function simulationEnd(): Carbon
    {
        return Carbon::parse(self::SIMULATION_END);
    }

    protected function seedCoreData(): void
    {
        Supplier::factory(self::SUPPLIER_COUNT)->create();
        Customer::factory(self::CUSTOMER_COUNT)->create();
        Product::factory(self::PRODUCT_COUNT)->create();

        $this->suppliers = Supplier::query()->get();
        $this->customers = Customer::query()->get();
        $this->products = Product::query()->with('supplier')->get();
        $this->warehouses = Warehouse::query()->orderBy('id')->get();

        $this->mainWarehouse = Warehouse::query()->where('code', 'WH-MAIN')->firstOrFail();
        $this->secondaryWarehouse = Warehouse::query()->where('code', 'WH-SEC')->firstOrFail();
    }

    protected function buildCatalogSegments(): void
    {
        $topSellerCount = max(24, (int) floor($this->products->count() * 0.2));
        $secondaryCount = max(65, (int) floor($this->products->count() * 0.55));

        $this->topSellers = $this->products->shuffle()->take($topSellerCount)->values();

        $this->secondaryAssortment = $this->topSellers
            ->concat($this->products->diff($this->topSellers)->shuffle()->take($secondaryCount - $this->topSellers->count()))
            ->unique('id')
            ->values();

        $this->productsBySupplier = $this->products->groupBy('supplier_id');

        $this->weightedPoolByWarehouse[$this->mainWarehouse->id] = $this->buildWeightedPool($this->products);
        $this->weightedPoolByWarehouse[$this->secondaryWarehouse->id] = $this->buildWeightedPool($this->secondaryAssortment);
    }

    protected function seedOpeningStock(): void
    {
        $openingDate = $this->simulationStart()->copy();

        Carbon::setTestNow($openingDate);

        foreach ($this->products as $product) {
            $isTopSeller = $this->topSellers->contains('id', $product->id);

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $this->mainWarehouse->id,
                'type' => 'in',
                'quantity' => $isTopSeller ? fake()->numberBetween(80, 180) : fake()->numberBetween(25, 90),
                'reference_type' => 'opening_balance',
                'reference_id' => null,
            ]);

            if ($this->secondaryAssortment->contains('id', $product->id)) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $this->secondaryWarehouse->id,
                    'type' => 'in',
                    'quantity' => $isTopSeller ? fake()->numberBetween(18, 65) : fake()->numberBetween(6, 30),
                    'reference_type' => 'opening_balance',
                    'reference_id' => null,
                ]);
            }
        }
    }

    protected function seedPurchaseFlow(): void
    {
        $purchaseOrderService = app(PurchaseOrderService::class);
        $start = $this->simulationStart()->copy()->addDays(3);
        $end = $this->simulationEnd()->copy()->subDays(10);

        for ($i = 0; $i < self::PURCHASE_ORDER_COUNT; $i++) {
            $supplier = $this->suppliers->random();
            $catalog = $this->productsBySupplier->get($supplier->id, collect());

            if ($catalog->isEmpty()) {
                continue;
            }

            $orderedAt = Carbon::instance(fake()->dateTimeBetween($start, $end))->setTime(fake()->numberBetween(7, 16), fake()->randomElement([0, 15, 30, 45]));
            $warehouse = fake()->boolean(72) ? $this->mainWarehouse : $this->secondaryWarehouse;
            $taxRate = $this->pickTaxRate();

            Carbon::setTestNow($orderedAt);

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => 'PO-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'ordered',
                'subtotal' => 0,
                'tax' => 0,
                'tax_rate' => $taxRate,
                'total' => 0,
                'ordered_at' => $orderedAt,
            ]);

            $items = $catalog->shuffle()->take(fake()->numberBetween(2, min(6, $catalog->count())));
            $subtotal = 0;

            foreach ($items as $product) {
                $quantity = $warehouse->is($this->mainWarehouse)
                    ? ($this->topSellers->contains('id', $product->id) ? fake()->numberBetween(18, 55) : fake()->numberBetween(8, 28))
                    : ($this->topSellers->contains('id', $product->id) ? fake()->numberBetween(8, 24) : fake()->numberBetween(4, 16));

                $costPrice = round((float) $product->cost_price * fake()->randomFloat(2, 0.97, 1.04), 2);
                $subtotal += $quantity * $costPrice;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'cost_price' => $costPrice,
                ]);
            }

            $tax = round($subtotal * ($taxRate / 100), 2);

            $purchaseOrder->update([
                'subtotal' => round($subtotal, 2),
                'tax' => $tax,
                'total' => round($subtotal + $tax, 2),
            ]);

            if (! fake()->boolean(92)) {
                continue;
            }

            $receivedAt = (clone $orderedAt)->addDays(max(1, $supplier->lead_time_days + fake()->numberBetween(-2, 4)));

            Carbon::setTestNow($receivedAt);
            $purchaseOrderService->receive($purchaseOrder->fresh());

            if (! fake()->boolean(78)) {
                continue;
            }

            $receivedPurchaseOrder = $purchaseOrder->fresh();
            $paymentDate = (clone $receivedAt)->addDays(fake()->numberBetween(3, 30));

            if ($paymentDate->greaterThan($this->simulationEnd())) {
                $paymentDate = $this->simulationEnd()->copy()->subDays(fake()->numberBetween(1, 5));
            }

            Carbon::setTestNow($paymentDate);

            $amount = fake()->boolean(72)
                ? (float) $receivedPurchaseOrder->total
                : round((float) $receivedPurchaseOrder->total * fake()->randomFloat(2, 0.35, 0.7), 2);

            $purchaseOrderService->recordSupplierPayment(
                $receivedPurchaseOrder,
                $amount,
                fake()->randomElement(['bank_transfer', 'sepa', 'card'])
            );
        }
    }

    protected function seedWarehouseTransfers(): void
    {
        $productService = app(ProductService::class);
        $start = $this->simulationStart()->copy()->addDays(10);
        $end = $this->simulationEnd()->copy()->subDays(5);

        for ($i = 0; $i < self::TRANSFER_COUNT; $i++) {
            $product = $this->secondaryAssortment->random();
            $availableInMain = $productService->calculateStockInWarehouse($product, $this->mainWarehouse->id);

            if ($availableInMain <= ($product->min_stock * 2)) {
                continue;
            }

            $quantity = min(fake()->numberBetween(3, 18), max(1, $availableInMain - $product->min_stock));
            $transferDate = Carbon::instance(fake()->dateTimeBetween($start, $end))->setTime(fake()->numberBetween(8, 17), fake()->randomElement([0, 30]));

            Carbon::setTestNow($transferDate);

            $transfer = WarehouseTransfer::create([
                'product_id' => $product->id,
                'from_warehouse_id' => $this->mainWarehouse->id,
                'to_warehouse_id' => $this->secondaryWarehouse->id,
                'quantity' => $quantity,
            ]);

            $productService->adjustStock(
                $product,
                $this->mainWarehouse->id,
                'out',
                $quantity,
                'warehouse_transfer',
                $transfer->id
            );

            $productService->adjustStock(
                $product,
                $this->secondaryWarehouse->id,
                'in',
                $quantity,
                'warehouse_transfer',
                $transfer->id
            );
        }
    }

    protected function seedSalesFlow(): void
    {
        $orderService = app(OrderService::class);
        $invoiceService = app(InvoiceService::class);
        for ($i = 0; $i < self::SALES_ORDER_COUNT; $i++) {
            $lifecycle = $this->pickOrderLifecycle();
            $customer = $this->pickCustomer();
            $warehouse = $this->pickWarehouseForCustomer($customer);
            $createdAt = $this->pickOrderCreatedAt($lifecycle);

            Carbon::setTestNow($createdAt);
            $order = $orderService->createDraftOrder($customer->id, $warehouse->id);

            $itemTarget = $customer->type === 'wholesale'
                ? fake()->randomElement([2, 3, 4, 5, 6])
                : fake()->randomElement([1, 1, 2, 2, 3, 4]);

            $usedProductIds = [];

            for ($attempt = 0; $attempt < 14 && $order->items()->count() < $itemTarget; $attempt++) {
                $product = $this->pickProductForWarehouse($warehouse, $usedProductIds);

                if (! $product) {
                    break;
                }

                $usedProductIds[] = $product->id;
                $quantity = $this->pickSalesQuantity($customer, $product);

                try {
                    $orderService->addItem($order, $product, $quantity);
                } catch (\Throwable) {
                    continue;
                }
            }

            $order->refresh();

            if ($order->items()->count() === 0) {
                $order->delete();
                continue;
            }

            if ($lifecycle === 'draft') {
                continue;
            }

            $confirmedAt = (clone $createdAt)->addMinutes(fake()->numberBetween(10, 240));
            Carbon::setTestNow($confirmedAt);
            $orderService->confirmOrder($order->fresh());

            if ($lifecycle === 'confirmed') {
                continue;
            }

            if ($lifecycle === 'cancelled') {
                $cancelledAt = (clone $confirmedAt)->addHours(fake()->numberBetween(1, 24));
                Carbon::setTestNow($cancelledAt);
                $orderService->cancelOrder($order->fresh());
                continue;
            }

            $shippedAt = (clone $confirmedAt)->addHours(fake()->numberBetween(2, 72));
            Carbon::setTestNow($shippedAt);
            $orderService->shipOrder($order->fresh());

            $order = $order->fresh();

            if ($lifecycle === 'completed') {
                $completedAt = (clone $shippedAt)->addDays(fake()->numberBetween(1, 4));
                Carbon::setTestNow($completedAt);
                $orderService->completeOrder($order);
                $order = $order->fresh();
            }

            if (! $this->shouldGenerateInvoiceFor($lifecycle)) {
                continue;
            }

            $invoiceAt = ($lifecycle === 'completed' ? $completedAt ?? $shippedAt : $shippedAt)->copy()->addDays(fake()->numberBetween(0, 2));
            Carbon::setTestNow($invoiceAt);
            $invoice = $invoiceService->generateFromOrder($order, $this->pickTaxRate());

            $this->seedInvoicePayments($invoice, $invoiceAt);
        }
    }

    protected function rebalanceFinanceDashboard(): void
    {
        $invoicePaymentService = app(InvoicePaymentService::class);

        $openInvoices = Invoice::query()
            ->withSum('payments', 'amount')
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderByDesc('issued_at')
            ->get();

        $futureDueInvoices = $openInvoices
            ->take(24)
            ->values();

        foreach ($futureDueInvoices as $index => $invoice) {
            $issuedAt = Carbon::parse($invoice->issued_at ?? now());
            $futureDueDate = $this->simulationEnd()->copy()->subDays(20 - min($index, 15));

            if ($futureDueDate->lessThanOrEqualTo($issuedAt)) {
                $futureDueDate = $issuedAt->copy()->addDays(10);
            }

            $invoice->update([
                'due_date' => $futureDueDate,
            ]);
        }

        $protectedIds = $futureDueInvoices->pluck('id')->all();

        $thisMonthTargets = $openInvoices
            ->reject(fn (Invoice $invoice) => in_array($invoice->id, $protectedIds, true))
            ->take(28)
            ->values();

        foreach ($thisMonthTargets as $index => $invoice) {
            $openAmount = round((float) $invoice->total - (float) ($invoice->payments_sum_amount ?? 0), 2);

            if ($openAmount <= 0) {
                continue;
            }

            $paymentDate = now()->copy()
                ->setDate(
                    $this->simulationEnd()->year,
                    $this->simulationEnd()->month,
                    min($index + 1, $this->simulationEnd()->day - 1)
                )
                ->setTime(10, 0);

            if ($paymentDate->greaterThan($this->simulationEnd())) {
                $paymentDate = $this->simulationEnd()->copy()->subDay()->setTime(10, 0);
            }

            Carbon::setTestNow($paymentDate);

            $invoicePaymentService->recordPayment(
                $invoice,
                $openAmount,
                fake()->randomElement(['bank_transfer', 'card'])
            );
        }

        $remainingOpenInvoices = Invoice::query()
            ->withSum('payments', 'amount')
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderBy('issued_at')
            ->get();

        $targetOutstanding = 650000.00;
        $currentOutstanding = $remainingOpenInvoices->sum(function (Invoice $invoice) {
            return max((float) $invoice->total - (float) ($invoice->payments_sum_amount ?? 0), 0);
        });

        foreach ($remainingOpenInvoices as $invoice) {
            if ($currentOutstanding <= $targetOutstanding) {
                break;
            }

            if (in_array($invoice->id, $protectedIds, true)) {
                continue;
            }

            $openAmount = round((float) $invoice->total - (float) ($invoice->payments_sum_amount ?? 0), 2);

            if ($openAmount <= 0) {
                continue;
            }

            $paymentDate = now()->copy()
                ->setDate(
                    $this->simulationEnd()->year,
                    $this->simulationEnd()->month,
                    max(1, $this->simulationEnd()->day - fake()->numberBetween(2, 25))
                )
                ->setTime(11, 0);

            Carbon::setTestNow($paymentDate);

            $invoicePaymentService->recordPayment(
                $invoice,
                $openAmount,
                fake()->randomElement(['bank_transfer', 'card'])
            );

            $currentOutstanding -= $openAmount;
        }
    }

    protected function seedInvoicePayments(Invoice $invoice, Carbon $invoiceAt): void
    {
        $invoicePaymentService = app(InvoicePaymentService::class);
        $scenario = fake()->randomElement([
            'none',
            'none',
            'partial',
            'full',
            'full',
            'split_paid',
        ]);

        if ($scenario === 'none') {
            return;
        }

        if ($scenario === 'partial') {
            $paymentDate = $this->clampPastDate((clone $invoiceAt)->addDays(fake()->numberBetween(5, 18)));
            $amount = round((float) $invoice->total * fake()->randomFloat(2, 0.35, 0.65), 2);

            Carbon::setTestNow($paymentDate);

            $invoicePaymentService->recordPayment(
                $invoice,
                $amount,
                fake()->randomElement(['bank_transfer', 'card', 'cash'])
            );

            return;
        }

        if ($scenario === 'full') {
            $paymentDate = $this->clampPastDate((clone $invoiceAt)->addDays(fake()->numberBetween(3, 21)));

            Carbon::setTestNow($paymentDate);

            $invoicePaymentService->recordPayment(
                $invoice,
                (float) $invoice->total,
                fake()->randomElement(['bank_transfer', 'card', 'cash'])
            );

            return;
        }

        $firstPaymentDate = $this->clampPastDate((clone $invoiceAt)->addDays(fake()->numberBetween(4, 12)));
        $secondPaymentDate = $this->clampPastDate((clone $firstPaymentDate)->addDays(fake()->numberBetween(7, 20)));
        $firstAmount = round((float) $invoice->total * fake()->randomFloat(2, 0.35, 0.6), 2);
        $secondAmount = round((float) $invoice->total - $firstAmount, 2);

        Carbon::setTestNow($firstPaymentDate);
        $invoicePaymentService->recordPayment(
            $invoice,
            $firstAmount,
            fake()->randomElement(['bank_transfer', 'card'])
        );

        Carbon::setTestNow($secondPaymentDate);
        $invoicePaymentService->recordPayment(
            $invoice,
            $secondAmount,
            fake()->randomElement(['bank_transfer', 'card'])
        );
    }

    protected function buildWeightedPool(Collection $products): array
    {
        $pool = [];

        foreach ($products as $product) {
            $weight = 1;

            if ($this->topSellers->contains('id', $product->id)) {
                $weight += 5;
            }

            if ((float) $product->price < 150) {
                $weight += 2;
            }

            if ((float) $product->price > 700) {
                $weight = max(1, $weight - 1);
            }

            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $product->id;
            }
        }

        return $pool;
    }

    protected function pickCustomer(): Customer
    {
        $wholesale = $this->customers->where('type', 'wholesale');

        return fake()->boolean(28)
            ? $wholesale->random()
            : $this->customers->where('type', 'retail')->random();
    }

    protected function pickWarehouseForCustomer(Customer $customer): Warehouse
    {
        if ($customer->type === 'wholesale') {
            return fake()->boolean(82) ? $this->mainWarehouse : $this->secondaryWarehouse;
        }

        return fake()->boolean(68) ? $this->mainWarehouse : $this->secondaryWarehouse;
    }

    protected function pickOrderCreatedAt(string $lifecycle): Carbon
    {
        if (in_array($lifecycle, ['draft', 'confirmed'])) {
            return Carbon::instance(
                fake()->dateTimeBetween(
                    $this->simulationEnd()->copy()->subDays(5),
                    $this->simulationEnd()->copy()->subHour()
                )
            );
        }

        return Carbon::instance(
            fake()->dateTimeBetween(
                $this->simulationStart(),
                $this->simulationEnd()->copy()->subDays(2)
            )
        );
    }

    protected function pickProductForWarehouse(Warehouse $warehouse, array $excludedIds = []): ?Product
    {
        $pool = $this->weightedPoolByWarehouse[$warehouse->id] ?? [];

        if ($pool === []) {
            return null;
        }

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $productId = fake()->randomElement($pool);

            if (in_array($productId, $excludedIds, true)) {
                continue;
            }

            return $this->products->firstWhere('id', $productId);
        }

        return null;
    }

    protected function pickSalesQuantity(Customer $customer, Product $product): int
    {
        if ($customer->type === 'wholesale') {
            return (float) $product->price > 500
                ? fake()->numberBetween(1, 3)
                : fake()->numberBetween(3, 10);
        }

        return (float) $product->price > 500
            ? 1
            : fake()->numberBetween(1, 3);
    }

    protected function shouldGenerateInvoiceFor(string $lifecycle): bool
    {
        return $lifecycle === 'completed'
            ? fake()->boolean(96)
            : fake()->boolean(82);
    }

    protected function pickTaxRate(): float
    {
        return fake()->randomElement([22.0, 22.0, 22.0, 9.5, 0.0]);
    }

    protected function pickOrderLifecycle(): string
    {
        return fake()->randomElement([
            'draft',
            'confirmed',
            'cancelled',
            'shipped',
            'shipped',
            'completed',
            'completed',
            'completed',
            'completed',
            'completed',
        ]);
    }

    protected function clampPastDate(Carbon $date): Carbon
    {
        if ($date->greaterThan($this->simulationEnd())) {
            return $this->simulationEnd()->copy()->subDays(fake()->numberBetween(1, 4));
        }

        return $date;
    }
}
