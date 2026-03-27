<?php

namespace App\Services;

use App\Accounting\AccountingEntryTypes;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function __construct(
        protected AccountingPeriodService $accountingPeriodService
    ) {
    }

    public function post(
        string $entryType,
        string $referenceType,
        int $referenceId,
        string $description,
        $postedAt,
        array $lines,
        ?int $reversalOfJournalEntryId = null
    ): JournalEntry {
        return DB::transaction(function () use (
            $entryType,
            $referenceType,
            $referenceId,
            $description,
            $postedAt,
            $lines,
            $reversalOfJournalEntryId
        ) {
            $existing = JournalEntry::where('entry_type', $entryType)
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->first();

            if ($existing) {
                return $existing->load(['lines.account', 'reversalEntry']);
            }

            $postingDate = Carbon::parse($postedAt);
            $this->accountingPeriodService->assertPostingAllowed($postingDate);

            $entry = JournalEntry::create([
                'entry_number' => $this->generateEntryNumber(),
                'entry_type' => $entryType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reversal_of_journal_entry_id' => $reversalOfJournalEntryId,
                'description' => $description,
                'posted_at' => $postingDate,
            ]);

            $lineNumber = 1;
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $account = $this->findAccount($line['account_code']);

                $debit = round((float) $line['debit'], 2);
                $credit = round((float) $line['credit'], 2);

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'line_number' => $lineNumber++,
                ]);

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \RuntimeException('Journal entry is not balanced.');
            }

            return $entry->load(['lines.account', 'reversalEntry']);
        });
    }

    public function reverse(JournalEntry $entry, ?User $user = null, $postedAt = null): JournalEntry
    {
        $entry->loadMissing(['lines.account', 'reversalEntry']);

        if ($entry->reversal_of_journal_entry_id) {
            throw new \RuntimeException('Reversal entries cannot be reversed again.');
        }

        if ($entry->reversalEntry) {
            throw new \RuntimeException('This journal entry has already been reversed.');
        }

        $reversalReferenceId = ($entry->id * 1000) + 1;

        $reversal = $this->post(
            entryType: AccountingEntryTypes::MANUAL_REVERSAL,
            referenceType: JournalEntry::class,
            referenceId: $reversalReferenceId,
            description: "Reversal of {$entry->entry_number}",
            postedAt: $postedAt ?? now(),
            reversalOfJournalEntryId: $entry->id,
            lines: $entry->lines
                ->sortBy('line_number')
                ->map(fn ($line) => [
                    'account_code' => $line->account->code,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                ])->all()
        );

        $entry->update([
            'reversed_at' => $reversal->posted_at,
            'reversed_by' => $user?->id,
        ]);

        return $reversal;
    }

    protected function findAccount(string $code): Account
    {
        return Account::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();
    }

    protected function generateEntryNumber(): string
    {
        $last = JournalEntry::orderByDesc('id')->value('id') ?? 0;

        return 'JE-' . str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }
}
