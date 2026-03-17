<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

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
}
