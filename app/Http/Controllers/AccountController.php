<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

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
        $validated = $request->validate(
            $this->accountService->validationRules()
        );

        $this->accountService->create(
            $validated,
            $request->boolean('is_active', true)
        );

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
        $validated = $request->validate(
            $this->accountService->validationRules($account->id)
        );

        $this->accountService->update(
            $account,
            $validated,
            $request->boolean('is_active', false)
        );

        return redirect()
            ->route('finance.accounts.index')
            ->with('success', 'Account updated.');
    }

    public function toggle(Account $account)
    {
        $this->accountService->toggle($account);

        return back()->with('success', 'Account status updated.');
    }
}
