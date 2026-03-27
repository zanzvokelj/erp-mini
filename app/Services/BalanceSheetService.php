<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    public function __construct(
        protected ProfitAndLossService $profitAndLossService
    ) {
    }

    public function build(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $rows = Account::query()
            ->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('journal_entries', function ($join) use ($dateFrom, $dateTo) {
                $join->on('journal_entries.id', '=', 'journal_lines.journal_entry_id');

                if ($dateFrom) {
                    $join->whereDate('journal_entries.posted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $join->whereDate('journal_entries.posted_at', '<=', $dateTo);
                }
            })
            ->whereIn('accounts.type', ['asset', 'liability'])
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.debit ELSE 0 END), 0) as total_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.credit ELSE 0 END), 0) as total_credit'),
            ])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $accounts = $rows->map(function ($row) {
            $totalDebit = round((float) $row->total_debit, 2);
            $totalCredit = round((float) $row->total_credit, 2);

            $amount = $row->type === 'asset'
                ? round($totalDebit - $totalCredit, 2)
                : round($totalCredit - $totalDebit, 2);

            return [
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'amount' => $amount,
            ];
        })->values();

        $profitSummary = $this->profitAndLossService->build($dateFrom, $dateTo)['summary'];

        $assetAccounts = $accounts->where('type', 'asset')->values();
        $liabilityAccounts = $accounts->where('type', 'liability')->values();

        $equityAccounts = collect([
            [
                'code' => 'CURRENT_EARNINGS',
                'name' => 'Current Earnings',
                'type' => 'equity',
                'amount' => round((float) $profitSummary['net_profit'], 2),
            ],
        ]);

        return [
            'asset_accounts' => $assetAccounts,
            'liability_accounts' => $liabilityAccounts,
            'equity_accounts' => $equityAccounts,
            'summary' => $this->buildSummary($assetAccounts, $liabilityAccounts, $equityAccounts),
        ];
    }

    protected function buildSummary(
        Collection $assetAccounts,
        Collection $liabilityAccounts,
        Collection $equityAccounts
    ): array {
        $totalAssets = round((float) $assetAccounts->sum('amount'), 2);
        $totalLiabilities = round((float) $liabilityAccounts->sum('amount'), 2);
        $totalEquity = round((float) $equityAccounts->sum('amount'), 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);

        return [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => $totalAssets === $totalLiabilitiesAndEquity,
        ];
    }
}
