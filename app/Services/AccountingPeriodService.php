<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use Carbon\CarbonInterface;

class AccountingPeriodService
{
    public function ensureYearExists(int $year): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $start = now()->setDate($year, $month, 1)->startOfMonth()->toDateString();
            $end = now()->setDate($year, $month, 1)->endOfMonth()->toDateString();

            AccountingPeriod::firstOrCreate(
                [
                    'start_date' => $start,
                    'end_date' => $end,
                ],
                [
                    'name' => now()->setDate($year, $month, 1)->format('F Y'),
                    'status' => 'open',
                ]
            );
        }
    }

    public function assertPostingAllowed(CarbonInterface $postingDate): void
    {
        if (! AccountingPeriod::query()->exists()) {
            return;
        }

        $period = AccountingPeriod::query()
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
}
