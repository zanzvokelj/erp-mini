<?php

namespace App\Http\Controllers;

use App\Services\BalanceSheetService;
use Illuminate\Http\Request;

class BalanceSheetController extends Controller
{
    public function __construct(
        protected BalanceSheetService $balanceSheetService
    ) {
    }

    public function index(Request $request)
    {
        $report = $this->balanceSheetService->build(
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString() ?: null,
        );

        return view('finance.balance-sheet', $report);
    }
}
