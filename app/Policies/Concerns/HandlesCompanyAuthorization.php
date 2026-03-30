<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesCompanyAuthorization
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->canAccessApp()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    protected function sameCompany(User $user, $model): bool
    {
        return (int) $user->company_id === (int) ($model->company_id ?? 0);
    }
}
