<?php

namespace App\Http\Controllers;

use App\Services\ProfitAndLossService;
use Illuminate\Http\Request;

class ProfitAndLossController extends Controller
{
    public function __construct(
        protected ProfitAndLossService $profitAndLossService
    ) {
    }

    public function index(Request $request)
    {
        $report = $this->profitAndLossService->build(
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString() ?: null,
        );

        return view('finance.profit-and-loss', $report);
    }
}
