<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_api_requires_customer()
    {
        $response = $this->postJson('/api/v1/orders', [
            'warehouse_id' => 1
        ]);

        $response->assertStatus(422);
    }

    public function test_order_api_requires_warehouse()
    {
        $response = $this->postJson('/api/v1/orders', [
            'customer_id' => 1
        ]);

        $response->assertStatus(422);
    }
}
