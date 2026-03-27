<?php

namespace App\Http\Controllers;

use App\Models\Account;

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
}
