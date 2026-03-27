<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Services\AccountingPeriodService;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function __construct(
        protected AccountingPeriodService $accountingPeriodService
    ) {
    }

    public function index(Request $request)
    {
        $year = $request->integer('year', (int) now()->year);

        $this->accountingPeriodService->ensureYearExists($year);

        $periods = AccountingPeriod::query()
            ->with('closedBy')
            ->whereYear('start_date', $year)
            ->orderBy('start_date')
            ->get();

        return view('finance.periods', [
            'periods' => $periods,
            'selectedYear' => $year,
        ]);
    }

    public function close(AccountingPeriod $period, Request $request)
    {
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $request->user()?->id,
        ]);

        return back()->with('success', "Period {$period->name} closed.");
    }

    public function reopen(AccountingPeriod $period)
    {
        $period->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return back()->with('success', "Period {$period->name} reopened.");
    }
}
