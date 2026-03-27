<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
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
            ->select([
                'accounts.id',
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
            $net = round($totalDebit - $totalCredit, 2);

            if (in_array($row->type, ['asset', 'expense'], true)) {
                $balanceAmount = abs($net);
                $balanceSide = $net >= 0 ? 'debit' : 'credit';
            } else {
                $net = round($totalCredit - $totalDebit, 2);
                $balanceAmount = abs($net);
                $balanceSide = $net >= 0 ? 'credit' : 'debit';
            }

            return [
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'balance_amount' => round($balanceAmount, 2),
                'balance_side' => $balanceAmount > 0 ? $balanceSide : 'balanced',
            ];
        })->values();

        return [
            'accounts' => $accounts,
            'totals' => $this->buildTotals($accounts),
        ];
    }

    protected function buildTotals(Collection $accounts): array
    {
        $totalDebit = round((float) $accounts->sum('total_debit'), 2);
        $totalCredit = round((float) $accounts->sum('total_credit'), 2);

        return [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => round($totalDebit, 2) === round($totalCredit, 2),
        ];
    }
}
