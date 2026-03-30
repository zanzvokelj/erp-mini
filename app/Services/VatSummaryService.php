<?php

namespace App\Services;

use App\Accounting\PostingMap;
use App\Models\Account;
use App\Services\Concerns\ScopesCurrentCompany;
use Illuminate\Support\Facades\DB;

class VatSummaryService
{
    use ScopesCurrentCompany;

    public function build(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $rows = Account::query()
            ->where('accounts.company_id', $this->companyId())
            ->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('journal_entries', function ($join) use ($dateFrom, $dateTo) {
                $join->on('journal_entries.id', '=', 'journal_lines.journal_entry_id');
                $join->whereColumn('journal_entries.company_id', 'accounts.company_id');

                if ($dateFrom) {
                    $join->whereDate('journal_entries.posted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $join->whereDate('journal_entries.posted_at', '<=', $dateTo);
                }
            })
            ->whereIn('accounts.code', [
                PostingMap::INPUT_VAT_RECEIVABLE,
                PostingMap::OUTPUT_VAT_PAYABLE,
            ])
            ->select([
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.debit ELSE 0 END), 0) as total_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.credit ELSE 0 END), 0) as total_credit'),
            ])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get()
            ->keyBy('code');

        $inputVat = $rows->get(PostingMap::INPUT_VAT_RECEIVABLE);
        $outputVat = $rows->get(PostingMap::OUTPUT_VAT_PAYABLE);

        $inputAmount = round(
            (float) (($inputVat?->total_debit ?? 0) - ($inputVat?->total_credit ?? 0)),
            2
        );

        $outputAmount = round(
            (float) (($outputVat?->total_credit ?? 0) - ($outputVat?->total_debit ?? 0)),
            2
        );

        $netVatLiability = round($outputAmount - $inputAmount, 2);

        return [
            'accounts' => [
                [
                    'code' => PostingMap::OUTPUT_VAT_PAYABLE,
                    'name' => $outputVat?->name ?? 'Output VAT Payable',
                    'amount' => $outputAmount,
                    'direction' => 'payable',
                ],
                [
                    'code' => PostingMap::INPUT_VAT_RECEIVABLE,
                    'name' => $inputVat?->name ?? 'Input VAT Receivable',
                    'amount' => $inputAmount,
                    'direction' => 'receivable',
                ],
            ],
            'summary' => [
                'output_vat' => $outputAmount,
                'input_vat' => $inputAmount,
                'net_vat_liability' => max($netVatLiability, 0),
                'net_vat_receivable' => abs(min($netVatLiability, 0)),
                'position' => $netVatLiability >= 0 ? 'payable' : 'receivable',
            ],
        ];
    }
}
