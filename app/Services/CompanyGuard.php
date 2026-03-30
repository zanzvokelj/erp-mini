<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class CompanyGuard
{
    public function assertSameCompany(array $models, string $message = 'Cross-company operation is not allowed.'): void
    {
        $companyIds = collect($models)
            ->filter()
            ->map(fn (Model $model) => $model->company_id ?? null)
            ->filter(fn ($companyId) => $companyId !== null)
            ->unique()
            ->values();

        if ($companyIds->count() > 1) {
            throw new \DomainException($message);
        }
    }

    public function assertCompanyId(int $expectedCompanyId, array $models, string $message = 'Cross-company operation is not allowed.'): void
    {
        $this->assertSameCompany($models, $message);

        foreach ($models as $model) {
            if (! $model instanceof Model) {
                continue;
            }

            if ($model->company_id !== null && (int) $model->company_id !== $expectedCompanyId) {
                throw new \DomainException($message);
            }
        }
    }
}
