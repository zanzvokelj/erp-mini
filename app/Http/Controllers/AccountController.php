<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::query()
            ->withCount('journalLines')
            ->orderBy('code')
            ->paginate(50);

        return view('finance.accounts', [
            'accounts' => $accounts,
        ]);
    }

    public function create()
    {
        return view('finance.accounts-create', [
            'types' => Account::TYPES,
            'categories' => Account::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAccount($request);

        Account::create($validated + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('finance.accounts.index')
            ->with('success', 'Account created.');
    }

    public function edit(Account $account)
    {
        return view('finance.accounts-edit', [
            'account' => $account,
            'types' => Account::TYPES,
            'categories' => Account::CATEGORIES,
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $validated = $this->validateAccount($request, $account->id);

        $account->update($validated + [
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()
            ->route('finance.accounts.index')
            ->with('success', 'Account updated.');
    }

    public function toggle(Account $account)
    {
        $account->update([
            'is_active' => ! $account->is_active,
        ]);

        return back()->with('success', 'Account status updated.');
    }

    protected function validateAccount(Request $request, ?int $accountId = null): array
    {
        $codeRule = Rule::unique('accounts', 'code');

        if ($accountId !== null) {
            $codeRule->ignore($accountId);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:255', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', Account::TYPES)],
            'category' => ['nullable', 'in:' . implode(',', Account::CATEGORIES)],
            'subtype' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
