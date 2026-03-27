<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Services\BalanceSheetService;
use App\Services\ProfitAndLossService;
use App\Services\TrialBalanceService;
use App\Services\VatSummaryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceApiController extends Controller
{
    public function __construct(
        protected TrialBalanceService $trialBalanceService,
        protected ProfitAndLossService $profitAndLossService,
        protected BalanceSheetService $balanceSheetService,
        protected VatSummaryService $vatSummaryService
    ) {
    }

    public function overview()
    {
        // 💰 TOTAL REVENUE
        $revenue = Invoice::where('status', 'paid')
            ->sum('total');

        // ⏳ OUTSTANDING
        $outstanding = Invoice::whereIn('status', ['draft', 'sent'])
            ->sum('total');

        // 🔴 OVERDUE (SUM)
        $overdue = Invoice::whereIn('status', ['draft', 'sent'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->sum('total');

        // 📈 THIS MONTH
        $thisMonth = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        // 🔥 OVERDUE LIST
        $overdueInvoices = Invoice::with('customer')
            ->whereIn('status', ['draft','sent'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'invoice_number',
                'customer_id',
                'total',
                'due_date'
            ]);

        return response()->json([
            'revenue' => (float) $revenue,
            'outstanding' => (float) $outstanding,
            'overdue' => (float) $overdue,
            'this_month' => (float) $thisMonth,
            'overdue_invoices' => $overdueInvoices // 🔥 TO JE NOVO
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
        $validated = $this->validateAccount($request);

        $account = Account::create($validated + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($account, 201);
    }

    public function updateAccount(Request $request, Account $account)
    {
        $validated = $this->validateAccount($request, $account->id);

        $account->update($validated + [
            'is_active' => $request->boolean('is_active', false),
        ]);

        return response()->json($account->fresh());
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
