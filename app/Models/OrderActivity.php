<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class OrderActivity extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'description',
        'created_by'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
