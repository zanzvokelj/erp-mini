<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Order;
use Carbon\Carbon;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\ErpSimulationSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpSimulationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulation_seeder_generates_recent_sme_sized_dataset(): void
    {
        $this->seed(AccountingSeeder::class);
        $this->seed(WarehouseSeeder::class);
        $this->seed(ErpSimulationSeeder::class);

        $windowStart = now()->copy()->subMonths(8)->startOfMonth();
        $windowEnd = now()->copy()->subDay()->endOfDay();

        $this->assertGreaterThanOrEqual(120, Order::query()->count());
        $this->assertLessThanOrEqual(260, Order::query()->count());

        $oldestOrder = Order::query()->oldest('created_at')->value('created_at');
        $latestOrder = Order::query()->latest('created_at')->value('created_at');

        $this->assertTrue(Carbon::parse($oldestOrder)->greaterThanOrEqualTo($windowStart));
        $this->assertTrue(Carbon::parse($latestOrder)->lessThan($windowEnd));

        $averageInvoiceTotal = (float) Invoice::query()->avg('total');
        $maxInvoiceTotal = (float) Invoice::query()->max('total');

        $this->assertGreaterThan(100, $averageInvoiceTotal);
        $this->assertLessThan(4000, $averageInvoiceTotal);
        $this->assertLessThan(12000, $maxInvoiceTotal);
    }
}
