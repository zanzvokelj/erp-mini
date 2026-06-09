<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use App\Services\ProfitAndLossService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_generation_creates_accounting_entry_as_side_effect()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('sales');

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'cost_price' => 40,
        ]);

        app(ProductService::class)->adjustStock($product, $warehouse->id, 'in', 10, 'restock');

        $orderService = app(OrderService::class);
        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 2);
        $orderService->confirmOrder($order);
        $orderService->shipOrder($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);

        $entry = JournalEntry::with('lines.account')
            ->where('entry_type', 'invoice_issued')
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertCount(2, $entry->lines);
        $this->assertEquals(200.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(200.0, (float) $entry->lines->sum('credit'));
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $entry->id,
            'debit' => 200.00,
        ]);

        $cogsEntry = JournalEntry::with('lines.account')
            ->where('entry_type', 'cost_of_goods_sold')
            ->where('reference_type', \App\Models\Order::class)
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNotNull($cogsEntry);
        $this->assertEquals(80.0, (float) $cogsEntry->lines->sum('debit'));
        $this->assertEquals(80.0, (float) $cogsEntry->lines->sum('credit'));
        $this->assertDatabaseHas('accounts', ['code' => '5000']);
    }

    public function test_invoice_generation_with_tax_records_output_vat()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('sales');

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'cost_price' => 40,
        ]);

        app(ProductService::class)->adjustStock($product, $warehouse->id, 'in', 10, 'restock');

        $orderService = app(OrderService::class);
        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 2);
        $orderService->confirmOrder($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order, 22);

        $entry = JournalEntry::with('lines.account')
            ->where('entry_type', 'invoice_issued')
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $invoice->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertCount(3, $entry->lines);
        $this->assertEquals(244.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(244.0, (float) $entry->lines->sum('credit'));
        $this->assertDatabaseHas('accounts', ['code' => '2100']);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $entry->id,
            'credit' => 44.00,
        ]);
    }

    public function test_invoice_numbers_are_company_scoped_and_sequential(): void
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('sales');

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'supplier_id' => $supplier->id,
            'price' => 100,
            'cost_price' => 40,
        ]);

        app(ProductService::class)->adjustStock($product, $warehouse->id, 'in', 20, 'restock');

        $orderService = app(OrderService::class);

        $firstOrder = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($firstOrder, $product, 1);
        $orderService->confirmOrder($firstOrder);
        $firstInvoice = app(InvoiceService::class)->generateFromOrder($firstOrder);

        $secondOrder = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($secondOrder, $product, 1);
        $orderService->confirmOrder($secondOrder);
        $secondInvoice = app(InvoiceService::class)->generateFromOrder($secondOrder);

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{1,4}-\d{4}-\d{5}$/', $firstInvoice->invoice_number);
        $this->assertStringEndsWith('-00001', $firstInvoice->invoice_number);
        $this->assertStringEndsWith('-00002', $secondInvoice->invoice_number);

        $otherCompany = Company::create([
            'name' => 'Northwind Systems',
            'slug' => 'northwind-systems',
            'is_active' => true,
        ]);

        $otherCustomer = Customer::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherWarehouse = Warehouse::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherSupplier = Supplier::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherProduct = Product::factory()->create([
            'company_id' => $otherCompany->id,
            'supplier_id' => $otherSupplier->id,
            'price' => 90,
            'cost_price' => 45,
        ]);

        app(ProductService::class)->adjustStock($otherProduct, $otherWarehouse->id, 'in', 10, 'restock');

        $otherOrder = Order::create([
            'company_id' => $otherCompany->id,
            'order_number' => 'ORD-OTH-001',
            'customer_id' => $otherCustomer->id,
            'warehouse_id' => $otherWarehouse->id,
            'status' => 'confirmed',
            'subtotal' => 90,
            'discount_total' => 0,
            'total' => 90,
            'confirmed_at' => now(),
        ]);

        $otherOrder->items()->create([
            'product_id' => $otherProduct->id,
            'quantity' => 1,
            'price_at_time' => 90,
            'cost_at_time' => 45,
        ]);

        $otherInvoice = Invoice::create([
            'company_id' => $otherCompany->id,
            'invoice_number' => $firstInvoice->invoice_number,
            'order_id' => $otherOrder->id,
            'customer_id' => $otherCustomer->id,
            'status' => 'draft',
            'subtotal' => 90,
            'tax' => 0,
            'total' => 90,
            'issued_at' => now(),
            'due_date' => now()->addDays(14),
        ]);

        $this->assertSame($firstInvoice->invoice_number, $otherInvoice->invoice_number);
    }

    public function test_payment_records_accounting_entry_as_side_effect()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => 80,
            'cost_price' => 35,
        ]);

        app(ProductService::class)->adjustStock($product, $warehouse->id, 'in', 10, 'restock');

        $orderService = app(OrderService::class);
        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 2);
        $orderService->confirmOrder($order);
        $orderService->shipOrder($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);

        $response = $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => 160,
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertOk();

        $entry = JournalEntry::with('lines.account')
            ->where('entry_type', 'payment_received')
            ->where('reference_type', \App\Models\Payment::class)
            ->latest()
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(160.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(160.0, (float) $entry->lines->sum('credit'));
        $this->assertDatabaseHas('accounts', ['code' => '1000']);
        $this->assertDatabaseHas('accounts', ['code' => '1100']);
    }

    public function test_purchase_order_receipt_records_accounting_entry_as_side_effect()
    {
        $this->seed(AccountingSeeder::class);

        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-ACC-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'cost_price' => 15,
        ]);

        app(PurchaseOrderService::class)->receive($po);

        $entry = JournalEntry::with('lines.account')
            ->where('entry_type', 'purchase_order_received')
            ->where('reference_type', PurchaseOrder::class)
            ->where('reference_id', $po->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(150.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(150.0, (float) $entry->lines->sum('credit'));
        $this->assertDatabaseHas('accounts', ['code' => '1200']);
        $this->assertDatabaseHas('accounts', ['code' => '2000']);
    }

    public function test_purchase_order_receipt_with_tax_records_input_vat()
    {
        $this->seed(AccountingSeeder::class);

        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-ACC-TAX-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered',
            'subtotal' => 150,
            'tax' => 33,
            'tax_rate' => 22,
            'total' => 183,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'cost_price' => 15,
        ]);

        app(PurchaseOrderService::class)->receive($po);

        $entry = JournalEntry::with('lines.account')
            ->where('entry_type', 'purchase_order_received')
            ->where('reference_type', PurchaseOrder::class)
            ->where('reference_id', $po->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertCount(3, $entry->lines);
        $this->assertEquals(183.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(183.0, (float) $entry->lines->sum('credit'));
        $this->assertDatabaseHas('accounts', ['code' => '1300']);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $entry->id,
            'debit' => 33.00,
        ]);
    }

    public function test_supplier_payment_records_accounting_entry_as_side_effect()
    {
        $this->seed(AccountingSeeder::class);

        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-ACC-002',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered',
            'total' => 150,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'cost_price' => 15,
        ]);

        app(PurchaseOrderService::class)->receive($po);

        $payment = app(PurchaseOrderService::class)->recordSupplierPayment(
            $po->fresh('payments'),
            100,
            'bank_transfer'
        );

        $this->assertInstanceOf(SupplierPayment::class, $payment);

        $entry = JournalEntry::with('lines.account')
            ->where('entry_type', 'supplier_payment')
            ->where('reference_type', SupplierPayment::class)
            ->where('reference_id', $payment->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(100.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(100.0, (float) $entry->lines->sum('credit'));
        $this->assertDatabaseHas('accounts', ['code' => '2000']);
        $this->assertDatabaseHas('accounts', ['code' => '1000']);
    }

    public function test_returning_a_completed_order_reverses_financial_entries(): void
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsUser('finance');

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'supplier_id' => $supplier->id,
            'price' => 100,
            'cost_price' => 40,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-ACC-RET-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'cost_price' => 40,
        ]);

        app(PurchaseOrderService::class)->receive($po);

        $orderService = app(OrderService::class);
        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 2);
        $orderService->confirmOrder($order);
        $orderService->shipOrder($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order->fresh());

        $paymentResponse = $this->postJson("/api/v1/invoices/{$invoice->id}/payments", [
            'amount' => 200,
            'payment_method' => 'bank_transfer',
        ]);

        $paymentResponse->assertOk();

        $orderService->returnOrder($order->fresh());

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'returned',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);

        $reversedEntries = JournalEntry::query()
            ->whereNotNull('reversal_of_journal_entry_id')
            ->count();

        $this->assertSame(3, $reversedEntries);

        $profitAndLoss = app(ProfitAndLossService::class)->build();

        $this->assertSame(0.0, (float) $profitAndLoss['summary']['revenue']);
        $this->assertSame(0.0, (float) $profitAndLoss['summary']['expenses']);

        $inventoryBalance = app(\App\Services\BalanceSheetService::class)->build();
        $assets = collect($inventoryBalance['asset_accounts'])->keyBy('code');

        $this->assertSame(200.0, (float) $assets['1200']['amount']);
    }
}
