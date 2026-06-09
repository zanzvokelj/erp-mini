<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AnalyticsService;
use App\Services\InvoicePaymentService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use Carbon\Carbon;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_financial_metrics_follow_the_ledger(): void
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        Carbon::setTestNow('2026-03-15 10:00:00');

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'supplier_id' => $supplier->id,
            'price' => 100,
            'cost_price' => 40,
        ]);

        $purchaseOrderService = app(PurchaseOrderService::class);
        $po = $purchaseOrderService->createDraft($supplier->id, $warehouse->id);
        $purchaseOrderService->addItem($po, $product->id, 10, 40);
        $po = $purchaseOrderService->recalculateTotals($po);
        $purchaseOrderService->markAsOrdered($po);
        $purchaseOrderService->receive($po->fresh());
        $purchaseOrderService->recordSupplierPayment($po->fresh(), 250, 'bank_transfer');

        $orderService = app(OrderService::class);
        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 2);
        $orderService->confirmOrder($order);
        $orderService->shipOrder($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order->fresh());
        app(InvoicePaymentService::class)->recordPayment($invoice, 200, 'bank_transfer');

        $analytics = app(AnalyticsService::class);

        $this->assertSame(200.0, (float) $analytics->totalRevenue());
        $this->assertSame(1, $analytics->totalOrders());
        $this->assertSame(200.0, (float) $analytics->averageOrderValue());
        $this->assertSame(200.0, (float) $analytics->revenueToday());
        $this->assertSame(120.0, (float) $analytics->totalProfit());
        $this->assertSame(320.0, (float) $analytics->inventoryValue());
        $this->assertSame(250.0, (float) $analytics->paidForInventory());
        $this->assertSame(0.25, round((float) $analytics->stockTurnover(), 2));

        $currentMonth = collect($analytics->monthlyRevenue())
            ->firstWhere('month', Carbon::now()->format('Y-m'));

        $this->assertNotNull($currentMonth);
        $this->assertSame(200.0, (float) $currentMonth['revenue']);

        Carbon::setTestNow();
    }
}
