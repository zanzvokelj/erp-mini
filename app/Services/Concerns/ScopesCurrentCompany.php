<?php

namespace App\Services\Concerns;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait ScopesCurrentCompany
{
    protected function companyId(): int
    {
        return app(CompanyContext::class)->id();
    }

    protected function scopeCompany(EloquentBuilder|QueryBuilder $query, ?string $table = null): EloquentBuilder|QueryBuilder
    {
        $column = $table ? "{$table}.company_id" : 'company_id';

        return $query->where($column, $this->companyId());
    }
}
