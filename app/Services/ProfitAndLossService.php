<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitAndLossService
{
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
            ->whereIn('accounts.type', ['revenue', 'expense'])
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
            $amount = $row->type === 'revenue'
                ? round($totalCredit - $totalDebit, 2)
                : round($totalDebit - $totalCredit, 2);

            return [
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'amount' => $amount,
            ];
        })->values();

        return [
            'revenue_accounts' => $accounts->where('type', 'revenue')->values(),
            'expense_accounts' => $accounts->where('type', 'expense')->values(),
            'summary' => $this->buildSummary($accounts),
        ];
    }

    protected function buildSummary(Collection $accounts): array
    {
        $revenue = round((float) $accounts->where('type', 'revenue')->sum('amount'), 2);
        $expenses = round((float) $accounts->where('type', 'expense')->sum('amount'), 2);
        $grossProfit = round($revenue - $expenses, 2);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit,
        ];
    }
}
