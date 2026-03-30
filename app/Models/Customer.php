<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InvoiceItem;

class Customer extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'discount_percent',
        'credit_limit'
    ];


    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }
}
