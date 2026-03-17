<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_api_can_create_order()
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create(); // ✅ DODAJ

        $response = $this->postJson('/api/v1/orders',[
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id // ✅ DODAJ
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders',[
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id
        ]);
    }
}
