<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Validation\Rule;

class AccountService
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected CompanyGuard $companyGuard
    ) {
    }

    public function validationRules(?int $accountId = null): array
    {
        $codeRule = Rule::unique('accounts', 'code')
            ->where(fn ($query) => $query->where('company_id', $this->companyContext->id()));

        if ($accountId !== null) {
            $codeRule->ignore($accountId);
        }

        return [
            'code' => ['required', 'string', 'max:255', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', Account::TYPES)],
            'category' => ['nullable', 'in:' . implode(',', Account::CATEGORIES)],
            'subtype' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function create(array $attributes, bool $isActive = true): Account
    {
        return Account::create($attributes + [
            'company_id' => $this->companyContext->id(),
            'is_active' => $isActive,
        ]);
    }

    public function update(Account $account, array $attributes, bool $isActive = false): Account
    {
        $this->companyGuard->assertCompanyId(
            $this->companyContext->id(),
            [$account],
            'Account must belong to the current company.'
        );

        $account->update($attributes + [
            'is_active' => $isActive,
        ]);

        return $account->fresh();
    }

    public function toggle(Account $account): Account
    {
        $this->companyGuard->assertCompanyId(
            $this->companyContext->id(),
            [$account],
            'Account must belong to the current company.'
        );

        $account->update([
            'is_active' => ! $account->is_active,
        ]);

        return $account->fresh();
    }
}
