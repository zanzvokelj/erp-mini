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

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_orders_cannot_oversell_stock()
    {
        $user = User::factory()->sales()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // stock = 5
        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            5,
            'restock'
        );

        $orderService = app(OrderService::class);

        // order 1
        $order1 = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order1, $product, 5);

        // order 2
        $order2 = $orderService->createDraftOrder($customer->id, $warehouse->id);

        //expecting fail
        $this->expectException(\Exception::class);

        $orderService->addItem($order2, $product, 5);
    }
}
