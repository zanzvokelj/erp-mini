<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_handle_1000_orders()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // initial stock
        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            2000,
            'restock'
        );

        $orderService = app(OrderService::class);

        for ($i = 0; $i < 1000; $i++) {
            $order = $orderService->createDraftOrder(
                $customer->id,
                $warehouse->id
            );

            $orderService->addItem($order, $product, 1);
            $orderService->confirmOrder($order);
        }

        $this->assertDatabaseCount('orders', 1000);
    }
}
