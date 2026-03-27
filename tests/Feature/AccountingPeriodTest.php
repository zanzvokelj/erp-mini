<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_is_blocked_when_period_is_closed()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        AccountingPeriod::create([
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'cost_price' => 40,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-CLOSED-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered',
            'subtotal' => 80,
            'tax' => 0,
            'tax_rate' => 0,
            'total' => 80,
            'received_at' => '2026-01-15 10:00:00',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'cost_price' => 40,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Accounting period January 2026 is closed.');

        app(AccountingService::class)->recordPurchaseOrderReceipt($po->fresh('items'));
    }

    public function test_posting_succeeds_when_period_is_open()
    {
        $this->seed(AccountingSeeder::class);
        $this->actingAsAdmin();

        AccountingPeriod::create([
            'name' => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => 'open',
        ]);

        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'cost_price' => 40,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-OPEN-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered',
            'subtotal' => 80,
            'tax' => 0,
            'tax_rate' => 0,
            'total' => 80,
            'received_at' => '2026-01-15 10:00:00',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'cost_price' => 40,
        ]);

        app(AccountingService::class)->recordPurchaseOrderReceipt($po->fresh('items'));

        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => PurchaseOrder::class,
            'reference_id' => $po->id,
            'entry_type' => 'purchase_order_received',
        ]);
    }

    public function test_accounting_periods_page_renders_and_can_generate_year()
    {
        $user = User::factory()->create([
            'email' => 'admin@admin.com',
        ]);
        $this->actingAs($user);

        $response = $this->get('/finance/periods?year=2026');

        $response->assertOk();
        $response->assertSee('Accounting Periods');
        $response->assertSee('January 2026');

        $this->assertEquals(12, AccountingPeriod::whereYear('start_date', 2026)->count());
    }
}
