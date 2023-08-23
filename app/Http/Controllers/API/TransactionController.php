<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $merchants = $request->input('merchants');

        try {
            //Filtering data transaction by id
            if ($id) {
                $transaction = Transaction::with(['items.product'])->where('users_id', $user)->find($id);
                if ($transaction)
                    return ResponseFormatter::success(
                        $transaction,
                        'Data transaksi dengan id transaksi ' . $id . ' berhasil diambil'
                    );
                else
                    return ResponseFormatter::error(
                        null,
                        'Data transaksi dengan id transaksi ' . $id . ' tidak ditemukan',
                        404.
                    );
            }
            //filtering data transaction by status
            if ($status) {
                $transaction = Transaction::with(['items.product'])->where('users_id', $user)->where('status', $status);
                if ($transaction)
                    return ResponseFormatter::success(
                        $transaction,
                        'Data transaksi dengan status transaksi ' . $status . ' berhasil diambil'
                    );
                else
                    return ResponseFormatter::error(
                        null,
                        'Data transaksi dengan status transaksi ' . $status . ' tidak ditemukan',
                        404.
                    );
            }

            if ($merchants) {
                $transaction = Transaction::with(['items.product.merchant'])->where('users_id', $user)->where('items.product.merchant.merchants_id', $merchants);
                if ($transaction)
                    return ResponseFormatter::success(
                        $transaction,
                        'Transaksi dengan id transaksi ' . $user . ' untuk merchant ' . $merchants . ' berhasil diambil'
                    );
                else
                    return ResponseFormatter::error(
                        null,
                        'Transaksi untuk merchant' . $merchants . 'tidak ditemukan',
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


    public function checkout(Request $request)
    {

        $user = Auth::user()->id;

        $validatedData = $request->validate([
            'total_price' => "required",
            'shipping_price' => "required",
            'payment' => "required|in:QRIS,CASH",
            'status' => "required|in:PENDING,SUCCESS,CANCELLED,FAILED,ONPROSSES",
            'point_usage' => "required|min:0",
            'address' => "nullable",
            'items' => "required|array",
            'items.*.id' => "required|exists:products,id",
            'items.*.quantity' => "required|min:1",
            'items.*.note' => "nullable",
        ]);

        $transaction = Transaction::create([
            'users_id' => $user,
            'total_price' => $validatedData['total_price'],
            'shipping_price' => $validatedData['shipping_price'],
            'payment' => $validatedData['payment'],
            'status' => $validatedData['status'],
            'point_usage' => $validatedData['point_usage'],
            'address' => $validatedData['address'],
        ]);

        foreach ($validatedData['items'] as $item) {
            TransactionItem::create([
                'users_id' => $user,
                'products_id' => $item['id'],
                'transactions_id' => $transaction['id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');
    }

    // public function confirmation(Request $request){
    //     $user = Auth::user()->id;
    //     $transaction_id = $request->input('transaction_id');
    //     $transaction = Transaction::where('users_id', $user)->where('transactions_id', $transaction_id );


    //     $validatedData = $request->validate([
    //         'status' => 'required|in:PENDING',
    //         'payment' => 'required|in:QRIS',
    //     ]);



    //     //Update Status Transaction
    //     $update = Transaction::where('id', $transaction_id)->update( [
    //         'status' => "ONPROSSES",
    //         'payment' => $validatedData['payment'],
    //     ]);

    //     return ResponseFormatter::success($update, 'Update Konfirmasi Berhasil');

    //     // if($request->hasFile('image')){
    //     //     //upload image
    //     //     $image = $validatedData['upload_image'];
    //     //     $image->storeAs('public/transaction', $image->hasName());
    //     //     //Delete old image
    //     //     Storage::delete('public/transaction/', $transaction->image);
    //     //     //Update image
    //     //     $transaction = Transaction::update([
    //     //         'image' => $image->hasName(),
    //     //         'status' => "ONPORSSES",
    //     //         'payment' => $validatedData['payment'],
    //     //     ]);

    //     //     return ResponseFormatter::success($transaction, 'Update Konfirmasi Berhasil');

    //     // }else{
    //     //     return ResponseFormatter::error(null, 'Update Konfirmasi Gagal', 500);

    //     // }
    // }

    public function confirmation(Request $request)
    {
        $user = Auth::user()->id;
        $transaction_id = $request->input('id');
        $image = $request->file('image');

        $validatedData = $request->validate([
            'status' => 'required|in:PENDING',
            'payment' => 'required|in:QRIS',
        ]);
        $transaction = Transaction::where('users_id', $user)->where('id', $transaction_id)->first();

        if ($request->hasFile('image')) {
            $imagePath = $image->store('Public/transactions');
            //$transaction->image = $imagePath;
            if ($transaction->image) {
                Storage::delete('Public/transactions');
                $transaction->image = $imagePath;
                return ResponseFormatter::success($transaction, 'Update Konfirmasi Berhasil');
            } else {
                $transaction->image = $imagePath;
                return ResponseFormatter::success($transaction, 'Update Konfirmasi Berhasil');
            }
            //Update transaction 
            // $transaction->status = $validatedData['status'];
            // $transaction->payment = $validatedData['payment'];
            // $transaction->save();
        }


    }

    // public function merchants(Request $request){
    //     $user = Auth::user()->id;
    //     $limit = $request->input('limit', 6);
    //     $merchants = $request->input('merchants');


    //     try{
    //         if($merchants){
    //             $transaction = Transaction::with('items.product.merchant')->where('users_id', $user)->where('items.product.merchant.merchant_id', $merchants);
    //             return ResponseFormatter::success($transaction, 'Transaksi id transaksi'.$user.'untuk merchant'.$merchants.'berhasil diambil');

    //         }else{
    //             return ResponseFormatter::error(null, 'Transaksi untuk merchant'.$merchants.'tidak ditemukan', 404);
    //         }

    //     }catch(\Throwable $th){
    //         return ResponseFormatter::error($th, 'Something Happen', 500);
    //     }
    // }
}
