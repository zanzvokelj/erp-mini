<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;
use App\Policies\Concerns\HandlesCompanyAuthorization;

class JournalEntryPolicy
{
    use HandlesCompanyAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('journal_entries.view');
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.view') && $this->sameCompany($user, $journalEntry);
    }

    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.reverse') && $this->sameCompany($user, $journalEntry);
    }
}
