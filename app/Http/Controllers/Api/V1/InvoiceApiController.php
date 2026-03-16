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

        return $query->latest()->paginate(20);
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
