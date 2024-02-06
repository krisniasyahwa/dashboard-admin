<?php
namespace App\Services;
use App\Models\Transaction;

class TransactionService{
    /* Helper For Detail Transaction */
    public function detailTransaction($id)
    {
        return Transaction::with('items.product.merchant')->where('id', $id)->first();
    }
}