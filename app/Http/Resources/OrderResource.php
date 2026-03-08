<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'total' => $this->total,
            'confirmed_at' => $this->confirmed_at,

            'customer' => $this->whenLoaded('customer'),

            'items' => $this->whenLoaded('items'),

            'created_at' => $this->created_at
        ];
    }
}
