<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
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
        $orderService->shipOrder($order);

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
}
