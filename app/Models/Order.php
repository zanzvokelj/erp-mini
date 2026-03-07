<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\OrderActivity;
class Order extends Model
{

    use HasFactory;
    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'subtotal',
        'discount_total',
        'total',
        'confirmed_at'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function activities()
    {
        return $this->hasMany(OrderActivity::class)
            ->latest();
    }
}
