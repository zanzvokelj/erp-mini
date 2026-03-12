<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
    public function index()
    {
        $query = DB::table('customers');

        /*
        SEARCH
        */

        if(request('search')) {
            $query->where('name','ILIKE','%'.request('search').'%');
        }

        if(request('type')) {
            $query->where('type', request('type'));
        }

        $customers = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show($id)
    {
        $customer = DB::table('customers')->find($id);

        $orders = DB::table('orders')
            ->where('customer_id', $id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $stats = DB::table('orders')
            ->where('customer_id', $id)
            ->selectRaw("
            COUNT(*) as total_orders,
            SUM(total) as total_revenue,
            AVG(total) as avg_order
        ")
            ->first();

        return view('customers.show', compact('customer','orders','stats'));
    }

}
