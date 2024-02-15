<?php
namespace App\Services;
use App\Models\Transaction;
use App\Models\TransactionItem;

class TransactionService{
    public function checkout($merchantId, $userId, $transactionType, $takeawayCharge,$totalPrice,$payment,$paymentType){
        return Transaction::create([
            'merchants_id' => $merchantId,
            'users_id' => $userId,
            'transaction_type' => $transactionType,
            'takeaway_charge' => $takeawayCharge,
            'total_price' => $totalPrice,
            'status' => 'PENDING',
            'payment' => $payment,
            'payment_type' => $paymentType]);
    }
    public function checkoutItem($userId,$itemId,$itemQuantity,$transactionId,$itemNote){
        return TransactionItem::create([
            'users_id' => $userId,
            'products_id' => $itemId,
            'quantity' => $itemQuantity,
            'transactions_id' => $transactionId,
            'note' => $itemNote
        ]);
    }
    public function detailTransaction($id)
    {
        return Transaction::with('items.product.merchant')->where('id', $id)->first();
    }
    public function history($userId){
        return Transaction::with(['merchant','items.product'])->where('users_id', $userId)->orderBy('created_at', 'desc')->get();

    }
}