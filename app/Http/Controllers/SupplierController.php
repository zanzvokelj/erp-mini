<?php

namespace App\Http\Controllers;

use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index()
    {
        $query = DB::table('suppliers')
            ->where('company_id', app(CompanyContext::class)->id());

        /*
        SEARCH
        */

        if(request('search')) {
            $query->where('name','ILIKE','%'.request('search').'%');
        }

        $suppliers = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }
}
