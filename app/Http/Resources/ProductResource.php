<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,

            'supplier' => $this->whenLoaded('supplier', [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name
            ])
        ];
    }
}
