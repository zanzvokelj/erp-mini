<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;

class OrderApiController extends Controller
{
    public function index()
    {
        return OrderResource::collection(
            Order::with(['customer','items.product'])
                ->latest()
                ->paginate(20)
        );
    }

    public function show(Order $order)
    {
        $order->load(['customer','items.product','activities']);

        return new OrderResource($order);
    }
}
