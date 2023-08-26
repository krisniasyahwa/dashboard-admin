<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Traits\FilterByDate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    use FilterByDate;
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


    // public function checkout(Request $request)
    // {

    //     $user = Auth::user()->id;

    //     $validatedData = $request->validate([
    //         'total_price' => "required",
    //         'shipping_price' => "required",
    //         'payment' => "required|in:QRIS,CASH",
    //         'status' => "required|in:PENDING,SUCCESS,CANCELLED,FAILED,ONPROSSES",
    //         'point_usage' => "required|min:0",
    //         'address' => "nullable",
    //         'items' => "required|array",
    //         'items.*.id' => "required|exists:products,id",
    //         'items.*.quantity' => "required|min:1",
    //         'items.*.note' => "nullable",
    //     ]);

    //     $transaction = Transaction::create([
    //         'users_id' => $user,
    //         'total_price' => $validatedData['total_price'],
    //         'shipping_price' => $validatedData['shipping_price'],
    //         'payment' => $validatedData['payment'],
    //         'status' => $validatedData['status'],
    //         'point_usage' => $validatedData['point_usage'],
    //         'address' => $validatedData['address'],
    //     ]);

    //     foreach ($validatedData['items'] as $item) {
    //         TransactionItem::create([
    //             'users_id' => $user,
    //             'products_id' => $item['id'],
    //             'transactions_id' => $transaction['id'],
    //             'quantity' => $item['quantity'],
    //         ]);
    //     }

    //     return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');
    // }

    public function validatecart($items)
    {
        //Get the first merchant_id from first item
            $merchants_id = $items[0]['product']['merchants_id'];

            //Loop to check if all item have same merchant_id
            foreach ($items as $item) {
    
                if ($item['product']['merchants_id'] !== $merchants_id) {
                    return false; //If item have different merchant_id return false
                }
            }

        return true; //If all item have same merchant_id return true
    }

    public function validationusergroups($user){
        $groupRegistered = ['admin', 'startup', 'dosen'];
        $user = User::with('usergroup.group')->where('id', $user)->get();
        $userGroup = $user[0]['usergroup']['group']['group_name'];
        if ($userGroup === $groupRegistered[0]) {
            return 'anda berhak mendapatkan point';
        }
        // if($userGroup === 'admin'){
        //     return 'anda berhak mendapatkan point';
        // }
        // if($userGroup === 'startup'){
        //     return 'anda berhak mendapatkan point';
        // }if($userGroup === 'dosen'){
        //     return 'anda berhak mendapatkan point';
        // }else{
        //     return 'anda tidak berhak mendapatkan point';
        // }
        
        return $userGroup;
    }

    public function checkout(Request $request)
    {
        $user = Auth::user()->id;

        $validatedDataTransaction = $request->validate([
            'address' => "nullable",
            'total_price' => "required",
            'status' => "required|in:PENDING,SUCCESS,CANCELLED,FAILED,ONPROSSES",
            'payment' => 'required|in:QRIS,MANUAL',
            'point_usage' => "required|min:0",
            'items' => "required|array",
            'items.*.id' => "required|exists:products,id",
            'items.*.quantity' => "required|min:1",
            'items.*.note' => "nullable",
        ]);

        $items = $validatedDataTransaction['items'];
        if (!$this->validatecart($items)) { 
            return response()->json([
                'message' => 'Checkout failed',
                'data' => 'Item from different merchant'
            ]);
        }

        $validatedUserGroup = $this->validationusergroups($user);

       
        
        // $transaction = Transaction::create([
        //     'users_id' => $user,
        //     'address' => $validatedDataTransaction['address'],
        //     'total_price' => $validatedDataTransaction['total_price'],
        //     'status' => $validatedDataTransaction['status'],
        //     'payment' => $validatedDataTransaction['payment'],
        //     'point_usage' => $validatedDataTransaction['point_usage'],

        // ]);

        // foreach ($items as $item){
        //     TransactionItem::create([
        //         'users_id' => $user,
        //         'products_id' => $item['id'],
        //         'transactions_id' => $transaction['id'],
        //         'quantity' => $item['quantity'],
        //         'note' =>  $item['note'],
        //     ]);
        // }

        //return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi berhasil');
        return response()->json([
            'message' => 'Checkout successful',
            'data' => $validatedUserGroup,
        ]);
    }

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

    public function filtertransactions(Request $request)
    {
        $user = Auth::user()->id;
        $limit = $request->input('limit|6');
        $type = $request->input('type');
        $payment = $request->input('payment');

        try {
            if ($type) {
                if ($type === 'today') {
                    $transaction = Transaction::with(['items.product'])->whereDate('created_at', Carbon::today())->where('users_id', $user)->get();
                    if ($transaction->count() != 0) {
                        return ResponseFormatter::success($transaction, 'Riwayat Transaksi untuk ' . $type . " berhasil diambil");
                    } else {
                        return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
                    }
                }
                if ($type === 'yesterday') {
                    $transaction = Transaction::with(['items.product'])->whereDate('created_at', Carbon::yesterday())->where('users_id', $user)->get();
                    if ($transaction->count() != 0) {
                        return ResponseFormatter::success($transaction, 'Riwayat Transaksi untuk ' . $type . " berhasil diambil");
                    } else {
                        return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
                    }
                }
                if ($type === 'lastmonth') {
                    $transaction = Transaction::with(['items.product'])->whereDate('created_at', Carbon::today()->subDays(30))->where('users_id', $user)->get();
                    if ($transaction->count() != 0) {
                        return ResponseFormatter::success($transaction, 'Riwayat Transaksi untuk ' . $type . " berhasil diambil");
                    } else {
                        return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
                    }
                }
                if ($type === 'lastyear') {
                    $filter = Carbon::now()->subYear();
                    $transaction = Transaction::with(['items.product'])->whereDate('created_at', $filter)->where('users_id', $user)->get();
                    if ($transaction->count() != 0) {
                        return ResponseFormatter::success($transaction, 'Riwayat Transaksi untuk ' . $type . " berhasil diambil");
                    } else {
                        return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
                    }
                }
                if ($type === 'lastweek') {
                    $filter = Carbon::now()->subDays(7);
                    $transaction = Transaction::with(['items.product'])->whereDate('created_at', $filter)->where('users_id', $user)->get();
                    if ($transaction->count() != 0) {
                        return ResponseFormatter::success($transaction, 'Riwayat Transaksi untuk ' . $type . " berhasil diambil");
                    } else {
                        return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
                    }
                }
                if ($type === 'last3Days') {
                    $filter = Carbon::now()->subDays(3);
                    $transaction = Transaction::with(['items.product'])->whereDate('created_at', $filter)->where('users_id', $user)->get();
                    if ($transaction->count() != 0) {
                        return ResponseFormatter::success($transaction, 'Riwayat Transaksi untuk ' . $type . " berhasil diambil");
                    } else {
                        return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
                    }
                }
            }
            if ($payment) {
                if ($payment === 'QRIS') {
                    $transaction = Transaction::with('items.product')->where('users_id', $user)->where('payment', $payment)->get();
                    return ResponseFormatter::success($transaction, 'Berhasil mengambil transaksi dengan metode pembayaran ' . $payment);
                }
                if ($payment === 'CASH') {
                    $transaction = Transaction::with('items.product')->where('users_id', $user)->where('payment', $payment)->get();
                    return ResponseFormatter::success($transaction, 'Berhasil mengambil transaksi dengan metode pembayaran ' . $payment);
                } else {
                    return ResponseFormatter::error(null, "Data transaksi tidak ditemukan", 400);
                }
            } else {
                return ResponseFormatter::error('null', 'Data transaksi tidak ditemukan');
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'something went wrong',
                'error' => $error
            ], 'Authenticated Fail', 500);
        }
    }
}
