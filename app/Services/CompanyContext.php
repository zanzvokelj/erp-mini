<?php

namespace App\Services;

use App\Models\Company;

class CompanyContext
{
    public function id(): int
    {
        $companyId = auth()->user()?->company_id;

        if ($companyId) {
            return (int) $companyId;
        }

        $fallbackId = Company::query()->orderBy('id')->value('id');

        if (! $fallbackId) {
            throw new \RuntimeException('No company available in the current context.');
        }

        return (int) $fallbackId;
    }
}
