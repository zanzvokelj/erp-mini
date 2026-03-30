<?php

namespace App\Policies;

use App\Models\AccountingPeriod;
use App\Models\User;
use App\Policies\Concerns\HandlesCompanyAuthorization;

class AccountingPeriodPolicy
{
    use HandlesCompanyAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accounting_periods.view');
    }

    public function view(User $user, AccountingPeriod $accountingPeriod): bool
    {
        return $user->hasPermission('accounting_periods.view')
            && $this->sameCompany($user, $accountingPeriod);
    }

    public function close(User $user, AccountingPeriod $accountingPeriod): bool
    {
        return $user->hasPermission('accounting_periods.manage')
            && $this->sameCompany($user, $accountingPeriod);
    }

    public function reopen(User $user, AccountingPeriod $accountingPeriod): bool
    {
        return $user->hasPermission('accounting_periods.manage')
            && $this->sameCompany($user, $accountingPeriod);
    }
}
