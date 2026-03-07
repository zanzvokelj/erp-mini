<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'sku',
        'name',
        'description',
        'price',
        'cost_price',
        'min_stock',
        'is_active'
    ];


    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockStatus(int $stock): string
    {
        if ($stock <= 0) {
            return 'out';
        }

        if ($stock < $this->min_stock) {
            return 'low';
        }

        return 'in';
    }

    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class);
    }
}
