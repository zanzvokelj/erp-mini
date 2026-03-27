<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class FinanceApiController extends Controller
{
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
}
