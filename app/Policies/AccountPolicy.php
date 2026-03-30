<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use App\Policies\Concerns\HandlesCompanyAuthorization;

class AccountPolicy
{
    use HandlesCompanyAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accounts.view');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.view') && $this->sameCompany($user, $account);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('accounts.manage');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.manage') && $this->sameCompany($user, $account);
    }

    public function toggle(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.manage') && $this->sameCompany($user, $account);
    }
}
