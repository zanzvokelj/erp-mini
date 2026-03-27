<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Carbon\Carbon;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_vat_summary_api_returns_output_input_and_net_position()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        $this->postSampleVatEntries();

        $response = $this->getJson('/api/v1/finance/vat-summary');

        $response->assertOk()
            ->assertJsonPath('summary.output_vat', 44)
            ->assertJsonPath('summary.input_vat', 33)
            ->assertJsonPath('summary.net_vat_liability', 11)
            ->assertJsonPath('summary.net_vat_receivable', 0)
            ->assertJsonPath('summary.position', 'payable');
    }

    public function test_vat_summary_api_respects_date_filter()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        $this->postSampleVatEntries();

        $response = $this->getJson('/api/v1/finance/vat-summary?date_to=2026-01-31');

        $response->assertOk()
            ->assertJsonPath('summary.output_vat', 44)
            ->assertJsonPath('summary.input_vat', 33)
            ->assertJsonPath('summary.net_vat_liability', 11);
    }

    public function test_vat_summary_web_page_renders_report()
    {
        $this->seed(AccountingSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@admin.com',
        ]);
        $this->actingAs($user);

        $this->postSampleVatEntries();

        $response = $this->get('/finance/vat-summary');

        $response->assertOk();
        $response->assertSee('VAT Summary');
        $response->assertSee('Output VAT Payable');
        $response->assertSee('Input VAT Receivable');
        $response->assertSee('11.00');
    }

    protected function postSampleVatEntries(): void
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
            'order_number' => 'SO-VAT-001',
        ]);

        $order->items()->create([
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'price_at_time' => 100,
            'cost_at_time' => 40,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-VAT-001',
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 200,
            'tax' => 44,
            'total' => 244,
            'issued_at' => Carbon::parse('2026-01-15 09:00:00'),
            'due_date' => Carbon::parse('2026-01-29 09:00:00'),
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-VAT-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'received',
            'subtotal' => 150,
            'tax' => 33,
            'tax_rate' => 22,
            'total' => 183,
            'received_at' => Carbon::parse('2026-01-12 08:00:00'),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => Product::factory()->create()->id,
            'quantity' => 10,
            'cost_price' => 15,
        ]);

        $accountingService->recordInvoiceIssued($invoice);
        $accountingService->recordPurchaseOrderReceipt($purchaseOrder->fresh('items'));

        Carbon::setTestNow();
    }
}
