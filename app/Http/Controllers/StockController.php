<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $query = Transaction::with(['items','merchant','user'])
                ->whereIn('status_payment',['EXPIRED','REJECTED']);

            $response =  DataTables::of($query)
            ->addColumn('action', function ($item) {
                $column = '
                <a class="inline-block border border-gray-700 bg-gray-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-gray-800 focus:outline-none focus:shadow-outline"
                    href="' . route('dashboard.transaction.show', $item->id) . '">
                    View
                </a>
                ';
                if ($item->stock_rollback_at == null) {
                    $column = $column . '
                        <a class="inline-block border border-blue-700 bg-blue-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-blue-800 focus:outline-none focus:shadow-outline"
                            href="' . route('dashboard.transaction.show', $item->id) . '">
                            Rollback
                        </a>
                    ';
                }
                return $column;
            })
            ->rawColumns(['action'])
            ->make();
            return $response;
    }


        $data = Transaction::with(['items','items.product'])->whereIn('status_payment',['EXPIRED','REJECTED'])->get();
        // return $data;
        // return view('pages.dashboard.stock.index');
        return view('pages.dashboard.stock.index',compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
