<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransfer extends Model
{
protected $fillable = [
'product_id',
'from_warehouse_id',
'to_warehouse_id',
'quantity'
];

public function product(): BelongsTo
{
return $this->belongsTo(Product::class);
}

public function fromWarehouse(): BelongsTo
{
return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
}

public function toWarehouse(): BelongsTo
{
return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
}
}
