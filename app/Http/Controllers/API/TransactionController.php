<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    //This function is used to get all transactioin data from databaase use request
    public function all(Request $request)
    {
        $id = $request->input('id'); //If request is id, id request will store in $id
        $limit = $request->input('limit', 6); //if request is limit, limit request will store in $limit, if limit request is null, $limit will store 6
        $status = $request->input('status'); //If request is status, status request will store in $status

        //This function is used to get personal transaction data from database use request id
        if ($id) {
            //Join transaction table, with product table
            $transaction = Transaction::with(['items.product'])->find($id); //Find transaction data with id request

            if ($transaction) //If transaction data from id request is found, return success response, and return $transaction data, and message
                return ResponseFormatter::success(
                    $transaction, 
                    'Data transaksi berhasil diambil'
                );
            else
                //If transaction data from id request not found, return error response, and return null, and message
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ada',
                    404
                );
        }
        //$transaction will store all transaction data from database use join product table use Auth::user() that means use id from user loged
        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);
        //This function is used to get transaction data from database use request status
        if ($status)
            $transaction->where('status', $status); //If request is status, $transaction will store transaction data with status request

        return ResponseFormatter::success(
            $transaction->paginate($limit), //Return success response, and return $transaction data with paginate method and limit request, and message
            'Data list transaksi berhasil diambil'
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

     //This function is used to send data transaction to database use request
    public function checkout(Request $request)
    {
        //$request use validate function to validate data request use formata array
        $request->validate([
            'items' => 'required|array', //items request is required and data type must be array
            'items.*.id' => 'exists:products,id', //items.*.id means all id in items request must be exists in products table where use id product 
            'total_price' => 'required', //total_price request is required
            'shipping_price' => 'required', //shipping_price request is required, thats means total_price and shipping_price request must be exists
            'payment' => 'required|in:QRIS,MANUAL', //payment request is required. Use in function to validate payment exists is QRIS or MANUAL
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED', //status request is required. This params use in function to validate status exists is PENDING, SUCCESS, CANCELLED, FAILED, SHIPPING, SHIPPED
        ]);
        //After all data request is validated, data will be create use create function. Output result use array format 
        $transaction = Transaction::create([
            'users_id' => Auth::user()->id, //users_id get id from user loged use Auth::user()
            'address' => $request->address, //address get data from request address
            'payment' => $request->payment, // payment will grab data from request payment 
            'total_price' => $request->total_price, //total_price wiil grab data from request total_price
            'shipping_price' => $request->shipping_price, //shipping_price will grab data from request shipping_price
            'status' => $request->status //status will grab data from request status
        ]);
        //This function is used to looping all array data request items and store to database use create function
        foreach ($request->items as $product) {
            TransactionItem::create([ //Call TransactionItem Model included create function to create data to database
                'users_id' => Auth::user()->id, //users_id grab id from user loged use Auth::user()
                'products_id' => $product['id'], //products_id grab id from request items array include product id
                'transactions_id' => $transaction->id, //transactions_id grab id from $transaction
                'quantity' => $product['quantity'] //quantity grab data from request items array include quantity product
            ]);
        }
        //If all data request is validated and all data request is stored to database, return success response, and return $transaction data, and message
        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil'); 
    }
}
