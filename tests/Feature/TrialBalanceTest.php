<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_api_returns_account_totals()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        $this->postSampleEntries();

        $response = $this->getJson('/api/v1/finance/trial-balance');

        $response->assertOk()
            ->assertJsonPath('totals.total_debit', 690)
            ->assertJsonPath('totals.total_credit', 690)
            ->assertJsonPath('totals.is_balanced', true);

        $accounts = collect($response->json('accounts'))->keyBy('code');

        $this->assertEquals(160.0, $accounts['1000']['total_debit']);
        $this->assertEquals(100.0, $accounts['1000']['total_credit']);
        $this->assertEquals(60.0, $accounts['1000']['balance_amount']);
        $this->assertEquals('debit', $accounts['1000']['balance_side']);

        $this->assertEquals(200.0, $accounts['1100']['total_debit']);
        $this->assertEquals(160.0, $accounts['1100']['total_credit']);
        $this->assertEquals(40.0, $accounts['1100']['balance_amount']);

        $this->assertEquals(150.0, $accounts['1200']['total_debit']);
        $this->assertEquals(80.0, $accounts['1200']['total_credit']);
        $this->assertEquals(70.0, $accounts['1200']['balance_amount']);

        $this->assertEquals(100.0, $accounts['2000']['total_debit']);
        $this->assertEquals(150.0, $accounts['2000']['total_credit']);
        $this->assertEquals(50.0, $accounts['2000']['balance_amount']);
        $this->assertEquals('credit', $accounts['2000']['balance_side']);

        $this->assertEquals(200.0, $accounts['4000']['total_credit']);
        $this->assertEquals(80.0, $accounts['5000']['total_debit']);
    }

    public function test_trial_balance_web_page_renders_account_balances()
    {
        $this->seed(AccountingSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@admin.com',
        ]);
        $this->actingAs($user);

        $this->postSampleEntries();

        $response = $this->get('/finance/trial-balance');

        $response->assertOk();
        $response->assertSee('Trial Balance');
        $response->assertSee('Accounts Receivable');
        $response->assertSee('Sales Revenue');
        $response->assertSee('Balanced');
    }

    protected function postSampleEntries(): void
    {
        $accountingService = app(AccountingService::class);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'shipped',
            'order_number' => 'SO-TB-001',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-TB-001',
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 200,
            'tax' => 0,
            'total' => 200,
            'issued_at' => now()->subDay(),
            'due_date' => now()->addDays(14),
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 160,
            'payment_method' => 'bank_transfer',
            'paid_at' => now(),
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TB-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'received',
            'total' => 150,
            'received_at' => now()->subDay(),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => \App\Models\Product::factory()->create()->id,
            'quantity' => 10,
            'cost_price' => 15,
        ]);

        $supplierPayment = \App\Models\SupplierPayment::create([
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 100,
            'payment_method' => 'bank_transfer',
            'paid_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => \App\Models\Product::factory()->create()->id,
            'quantity' => 2,
            'price_at_time' => 100,
            'cost_at_time' => 40,
        ]);

        $accountingService->recordInvoiceIssued($invoice);
        $accountingService->recordPaymentReceived($payment);
        $accountingService->recordPurchaseOrderReceipt($purchaseOrder->fresh('items'));
        $accountingService->recordSupplierPayment($supplierPayment);
        $accountingService->recordCostOfGoodsSold($order->fresh('items'));
    }
}
