<?php

namespace App\Http\Controllers;

use App\Services\VatSummaryService;
use Illuminate\Http\Request;

class VatSummaryController extends Controller
{
    public function __construct(
        protected VatSummaryService $vatSummaryService
    ) {
    }

    public function index(Request $request)
    {
        $report = $this->vatSummaryService->build(
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString() ?: null,
        );

        return view('finance.vat-summary', $report);
    }
}
