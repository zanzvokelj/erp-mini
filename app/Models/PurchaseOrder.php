<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_id',
        'warehouse_id',
        'status',
        'subtotal',
        'tax',
        'tax_rate',
        'total',
        'ordered_at',
        'received_at'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
