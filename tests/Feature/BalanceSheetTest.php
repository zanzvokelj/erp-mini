<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
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

class BalanceSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_sheet_api_returns_assets_liabilities_and_equity()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $this->postSampleEntries();

        $response = $this->getJson('/api/v1/finance/balance-sheet');

        $response->assertOk()
            ->assertJsonPath('summary.total_assets', 170)
            ->assertJsonPath('summary.total_liabilities', 50)
            ->assertJsonPath('summary.total_equity', 120)
            ->assertJsonPath('summary.total_liabilities_and_equity', 170)
            ->assertJsonPath('summary.is_balanced', true);

        $assets = collect($response->json('asset_accounts'))->keyBy('code');
        $liabilities = collect($response->json('liability_accounts'))->keyBy('code');
        $equity = collect($response->json('equity_accounts'))->keyBy('code');

        $this->assertEquals(60.0, $assets['1000']['amount']);
        $this->assertEquals(40.0, $assets['1100']['amount']);
        $this->assertEquals(70.0, $assets['1200']['amount']);
        $this->assertEquals(50.0, $liabilities['2000']['amount']);
        $this->assertEquals(120.0, $equity['CURRENT_EARNINGS']['amount']);
    }

    public function test_balance_sheet_api_respects_date_filter()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $this->postSampleEntries();

        $response = $this->getJson('/api/v1/finance/balance-sheet?date_to=2026-01-31');

        $response->assertOk()
            ->assertJsonPath('summary.total_assets', 350)
            ->assertJsonPath('summary.total_liabilities', 150)
            ->assertJsonPath('summary.total_equity', 200)
            ->assertJsonPath('summary.total_liabilities_and_equity', 350)
            ->assertJsonPath('summary.is_balanced', true);
    }

    public function test_balance_sheet_web_page_renders_report()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $this->postSampleEntries();

        $response = $this->get('/finance/balance-sheet');

        $response->assertOk();
        $response->assertSee('Balance Sheet');
        $response->assertSee('Accounts Receivable');
        $response->assertSee('Accounts Payable');
        $response->assertSee('Current Earnings');
        $response->assertSee('Balanced');
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
            'order_number' => 'SO-BS-001',
        ]);

        $order->items()->create([
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'price_at_time' => 100,
            'cost_at_time' => 40,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-BS-001',
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
            'po_number' => 'PO-BS-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'received',
            'total' => 150,
            'received_at' => Carbon::parse('2026-01-12 08:00:00'),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => Product::factory()->create()->id,
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
