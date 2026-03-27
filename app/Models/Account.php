<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'subtype',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public const TYPES = [
        'asset',
        'liability',
        'equity',
        'revenue',
        'expense',
    ];

    public const CATEGORIES = [
        'current_asset',
        'non_current_asset',
        'current_liability',
        'non_current_liability',
        'equity',
        'operating_revenue',
        'other_revenue',
        'cost_of_sales',
        'operating_expense',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
