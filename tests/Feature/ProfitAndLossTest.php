<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Carbon\Carbon;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitAndLossTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_and_loss_api_returns_revenue_expense_and_profit()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $this->postSampleEntries();

        $response = $this->getJson('/api/v1/finance/profit-and-loss');

        $response->assertOk()
            ->assertJsonPath('summary.revenue', 200)
            ->assertJsonPath('summary.expenses', 80)
            ->assertJsonPath('summary.gross_profit', 120)
            ->assertJsonPath('summary.net_profit', 120);

        $revenueAccounts = collect($response->json('revenue_accounts'))->keyBy('code');
        $expenseAccounts = collect($response->json('expense_accounts'))->keyBy('code');

        $this->assertEquals(200.0, $revenueAccounts['4000']['amount']);
        $this->assertEquals(80.0, $expenseAccounts['5000']['amount']);
    }

    public function test_profit_and_loss_api_respects_date_filter()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $this->postSampleEntries();

        $response = $this->getJson('/api/v1/finance/profit-and-loss?date_to=2026-01-31');

        $response->assertOk()
            ->assertJsonPath('summary.revenue', 200)
            ->assertJsonPath('summary.expenses', 0)
            ->assertJsonPath('summary.gross_profit', 200)
            ->assertJsonPath('summary.net_profit', 200);
    }

    public function test_profit_and_loss_web_page_renders_report()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $this->postSampleEntries();

        $response = $this->get('/finance/profit-and-loss');

        $response->assertOk();
        $response->assertSee('Profit &amp; Loss', false);
        $response->assertSee('Sales Revenue');
        $response->assertSee('Cost of Goods Sold');
        $response->assertSee('120.00');
    }

    protected function postSampleEntries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-10 12:00:00'));

        $accountingService = app(AccountingService::class);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'shipped',
            'order_number' => 'SO-PL-001',
        ]);

        $order->items()->create([
            'product_id' => \App\Models\Product::factory()->create()->id,
            'quantity' => 2,
            'price_at_time' => 100,
            'cost_at_time' => 40,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-PL-001',
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 200,
            'tax' => 0,
            'total' => 200,
            'issued_at' => Carbon::parse('2026-01-15 09:00:00'),
            'due_date' => Carbon::parse('2026-01-29 09:00:00'),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 160,
            'payment_method' => 'bank_transfer',
            'paid_at' => Carbon::parse('2026-02-05 12:00:00'),
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-PL-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'received',
            'total' => 150,
            'received_at' => Carbon::parse('2026-01-12 08:00:00'),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => \App\Models\Product::factory()->create()->id,
            'quantity' => 10,
            'cost_price' => 15,
        ]);

        $supplierPayment = SupplierPayment::create([
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 100,
            'payment_method' => 'bank_transfer',
            'paid_at' => Carbon::parse('2026-02-06 14:00:00'),
        ]);

        $accountingService->recordInvoiceIssued($invoice);
        $accountingService->recordPaymentReceived($payment);
        $accountingService->recordPurchaseOrderReceipt($purchaseOrder->fresh('items'));
        $accountingService->recordSupplierPayment($supplierPayment);
        $accountingService->recordCostOfGoodsSold($order->fresh('items'));

        Carbon::setTestNow();
    }
}
