<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use Carbon\CarbonInterface;

class AccountingPeriodService
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected CompanyGuard $companyGuard
    ) {
    }

    public function ensureYearExists(int $year): void
    {
        $companyId = $this->companyContext->id();

        for ($month = 1; $month <= 12; $month++) {
            $start = now()->setDate($year, $month, 1)->startOfMonth()->toDateString();
            $end = now()->setDate($year, $month, 1)->endOfMonth()->toDateString();

            AccountingPeriod::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'start_date' => $start,
                    'end_date' => $end,
                ],
                [
                    'company_id' => $companyId,
                    'name' => now()->setDate($year, $month, 1)->format('F Y'),
                    'status' => 'open',
                ]
            );
        }
    }

    public function assertPostingAllowed(CarbonInterface $postingDate): void
    {
        $companyId = $this->companyContext->id();

        if (! AccountingPeriod::query()->where('company_id', $companyId)->exists()) {
            return;
        }

        $period = AccountingPeriod::query()
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $postingDate->toDateString())
            ->whereDate('end_date', '>=', $postingDate->toDateString())
            ->first();

        if (! $period) {
            throw new \RuntimeException('No accounting period configured for the posting date.');
        }

        if ($period->status === 'closed') {
            throw new \RuntimeException("Accounting period {$period->name} is closed.");
        }
    }

    public function close(AccountingPeriod $period, ?int $userId = null): AccountingPeriod
    {
        $this->companyGuard->assertCompanyId(
            $this->companyContext->id(),
            [$period],
            'Accounting period must belong to the current company.'
        );

        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $userId,
        ]);

        return $period->fresh();
    }

    public function reopen(AccountingPeriod $period): AccountingPeriod
    {
        $this->companyGuard->assertCompanyId(
            $this->companyContext->id(),
            [$period],
            'Accounting period must belong to the current company.'
        );

        $period->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $period->fresh();
    }
}
