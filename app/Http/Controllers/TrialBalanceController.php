<?php

namespace App\Http\Controllers;

use App\Services\TrialBalanceService;
use Illuminate\Http\Request;

class TrialBalanceController extends Controller
{
    public function __construct(
        protected TrialBalanceService $trialBalanceService
    ) {
    }

    public function index(Request $request)
    {
        $trialBalance = $this->trialBalanceService->build(
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString() ?: null,
        );

        return view('finance.trial-balance', [
            'accounts' => $trialBalance['accounts'],
            'totals' => $trialBalance['totals'],
        ]);
    }
}
