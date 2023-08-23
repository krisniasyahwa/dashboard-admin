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

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function index(Request $request)
    {
        $user = Auth::user()->id;
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        try {
            //Filtering data transaction by id
            if ($id) {
                $transaction = Transaction::with(['items.product'])->where('users_id', $user)->find($id);
                if ($transaction)
                    return ResponseFormatter::success(
                        $transaction,
                        'Data transaksi dengan id transaksi '.$id.' berhasil diambil'
                    );
                else
                    return ResponseFormatter::error(
                        null,
                        'Data transaksi dengan id transaksi '.$id.' tidak ditemukan',
                        404.
                    );
            }
            //filtering data transaction by status
            if ($status) {
                $transaction = Transaction::with(['items.product'])->where('users_id', $user)->where('status', $status);
                if ($transaction)
                    return ResponseFormatter::success(
                        $transaction,
                        'Data transaksi dengan status transaksi '.$status.' berhasil diambil'
                    );
                else
                    return ResponseFormatter::error(
                        null,
                        'Data transaksi dengan status transaksi '.$status.' tidak ditemukan',
                        404.
                    );
            }

            //Get all data transaction by user loged
            $transaction = Transaction::with(['items.product'])->where('users_id', $user);
            if ($transaction->count() == 0) {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ditemukan',
                    404
                );
            } else {
                return ResponseFormatter::success($transaction->paginate($limit), 'Data list transaksi berhasil diambil');
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Something Happen', 500);
        }
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
