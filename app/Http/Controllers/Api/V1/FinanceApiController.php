<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Services\AccountService;
use App\Services\BalanceSheetService;
use App\Services\ProfitAndLossService;
use App\Services\TrialBalanceService;
use App\Services\VatSummaryService;
use Illuminate\Http\Request;

class FinanceApiController extends Controller
{
    public function __construct(
        protected AccountService $accountService,
        protected TrialBalanceService $trialBalanceService,
        protected ProfitAndLossService $profitAndLossService,
        protected BalanceSheetService $balanceSheetService,
        protected VatSummaryService $vatSummaryService
    ) {
    }

    public function overview(Request $request)
    {
        // 💰 TOTAL REVENUE
        $revenue = Invoice::where('status', 'paid')
            ->sum('total');

        $openInvoices = Invoice::query()
            ->withSum('payments', 'amount')
            ->whereNotIn('status', ['paid', 'cancelled']);

        // ⏳ OUTSTANDING
        $outstanding = (clone $openInvoices)
            ->get(['id', 'total'])
            ->sum(function (Invoice $invoice) {
                $paid = (float) ($invoice->payments_sum_amount ?? 0);

                return max((float) $invoice->total - $paid, 0);
            });

        // 🔴 OVERDUE (SUM)
        $overdue = (clone $openInvoices)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->get(['id', 'total'])
            ->sum(function (Invoice $invoice) {
                $paid = (float) ($invoice->payments_sum_amount ?? 0);

                return max((float) $invoice->total - $paid, 0);
            });

        // 📈 THIS MONTH
        $thisMonth = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        // 🔥 OVERDUE LIST
        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        $overdueInvoices = Invoice::with('customer')
            ->withSum('payments', 'amount')
            ->whereNotIn('status', ['paid','cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->latest()
            ->paginate($perPage, [
                'id',
                'invoice_number',
                'customer_id',
                'total',
                'due_date'
            ])
            ->through(function (Invoice $invoice) {
                $paid = (float) ($invoice->payments_sum_amount ?? 0);
                $invoice->open_amount = round(max((float) $invoice->total - $paid, 0), 2);

                return $invoice;
            })
            ->withQueryString();

        return response()->json([
            'revenue' => (float) $revenue,
            'outstanding' => round((float) $outstanding, 2),
            'overdue' => round((float) $overdue, 2),
            'this_month' => (float) $thisMonth,
            'overdue_invoices' => $overdueInvoices->items(),
            'overdue_pagination' => [
                'current_page' => $overdueInvoices->currentPage(),
                'last_page' => $overdueInvoices->lastPage(),
                'per_page' => $overdueInvoices->perPage(),
                'total' => $overdueInvoices->total(),
                'from' => $overdueInvoices->firstItem(),
                'to' => $overdueInvoices->lastItem(),
                'has_more_pages' => $overdueInvoices->hasMorePages(),
            ],
        ]);
    }

    public function journalEntries(Request $request)
    {
        $entries = JournalEntry::with(['lines.account'])
            ->when($request->filled('entry_type'), function ($query) use ($request) {
                $query->where('entry_type', $request->string('entry_type'));
            })
            ->when($request->filled('reference_type'), function ($query) use ($request) {
                $query->where('reference_type', $request->string('reference_type'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('posted_at', '>=', $request->string('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('posted_at', '<=', $request->string('date_to'));
            })
            ->latest('posted_at')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($entries);
    }

    public function trialBalance(Request $request)
    {
        return response()->json(
            $this->trialBalanceService->build(
                $request->string('date_from')->toString() ?: null,
                $request->string('date_to')->toString() ?: null,
            )
        );
    }

    public function profitAndLoss(Request $request)
    {
        return response()->json(
            $this->profitAndLossService->build(
                $request->string('date_from')->toString() ?: null,
                $request->string('date_to')->toString() ?: null,
            )
        );
    }

    public function balanceSheet(Request $request)
    {
        return response()->json(
            $this->balanceSheetService->build(
                $request->string('date_from')->toString() ?: null,
                $request->string('date_to')->toString() ?: null,
            )
        );
    }

    public function accounts(Request $request)
    {
        return response()->json(
            Account::query()
                ->withCount('journalLines')
                ->orderBy('code')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function vatSummary(Request $request)
    {
        return response()->json(
            $this->vatSummaryService->build(
                $request->string('date_from')->toString() ?: null,
                $request->string('date_to')->toString() ?: null,
            )
        );
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate(
            $this->accountService->validationRules()
        );

        $account = $this->accountService->create(
            $validated,
            $request->boolean('is_active', true)
        );

        return response()->json($account, 201);
    }

    public function updateAccount(Request $request, Account $account)
    {
        $validated = $request->validate(
            $this->accountService->validationRules($account->id)
        );

        $account = $this->accountService->update(
            $account,
            $validated,
            $request->boolean('is_active', false)
        );

        return response()->json($account);
    }
}
