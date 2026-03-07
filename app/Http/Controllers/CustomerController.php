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

}
