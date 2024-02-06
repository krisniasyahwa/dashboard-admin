<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        $transactions = Transaction::with(['user','merchant','items','items.product'])->orderBy('id','desc')->get();
        // return $transactions;
        return view('pages.new-dashboard.transaction.index',compact('transactions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pages.new-dashboard.transaction.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show(Transaction $transaction)
    {
        // * Id Dropdown Selection Logic
        // Calculate the range of IDs to fetch (current ID - 50 to current ID + 50)
        $startId = max(1, $transaction->id - 50);
        $endId = $transaction->id + 50;

        // Fetch only the transaction IDs within the specified range
        $allTransactionIds = Transaction::whereBetween('id', [$startId, $endId])
            ->orderBy('id','desc')
            ->pluck('id')
            ->toArray();

        // Convert the array to a string for use in view
        $allTransactionIdsString = "'" . implode("','", $allTransactionIds) . "'";

        // * main transaction data
        $transaction = $transaction->with(['user','merchant','items','items.product', 'items.product.galleries'])->find($transaction->id);
        // return $transaction;
        return view('pages.new-dashboard.transaction.show',compact('transaction','allTransactionIdsString'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
