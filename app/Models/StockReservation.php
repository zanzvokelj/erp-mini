<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    protected $fillable = [
        'product_id',
        'order_id',
        'quantity',
        'expires_at'
    ];
}
