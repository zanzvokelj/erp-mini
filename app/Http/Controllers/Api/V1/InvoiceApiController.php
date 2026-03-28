<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer','payments']);
        $sortBy = $request->string('sort_by')->toString() ?: 'created';

        if ($request->search) {

            $query->where(function($q) use ($request) {

                $q->where('invoice_number','like','%'.$request->search.'%')
                    ->orWhereHas('customer', function($q2) use ($request) {

                        $q2->where('name','like','%'.$request->search.'%');

                    });

            });

        }

        if ($request->status) {
            $query->where('status',$request->status);
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        if ($sortBy === 'due') {
            $query
                ->orderByRaw('due_date IS NULL')
                ->orderByDesc('due_date');
        } else {
            $query
                ->orderByDesc('created_at')
                ->orderByDesc('issued_at');
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'order',
            'items.product',
            'payments'
        ]);

        return response()->json($invoice);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('items.product', 'customer');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function overdue()
    {
        return Invoice::with('customer')
            ->overdue()
            ->latest()
            ->paginate(20);
    }

}
