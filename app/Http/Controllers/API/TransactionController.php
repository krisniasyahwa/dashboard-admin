<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\Product;
use App\Traits\FilterByDate;
use App\Http\Requests\ImageStoreRequest;
use App\Models\Cart;
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
        try {

            $unpaidTransactions = Transaction::with('items.product')->where('users_id', $user)->where('status_payment', 'UNPAID')->orderBy('created_at', 'desc')->get();
            $paidTransactions = Transaction::with('items.product')->where('users_id', $user)->where('status_payment', 'PAID')->orderBy('created_at', 'desc')->get();
            if ($unpaidTransactions->isEmpty() && $paidTransactions->isEmpty()) {
                return ResponseFormatter::error(null, 'Transactions Not Found', 400);
            } elseif ($paidTransactions->isNotEmpty()) {
                if ($unpaidTransactions->isNotEmpty()) {
                    $transactions = $unpaidTransactions->concat($paidTransactions);
                    return ResponseFormatter::success($transactions, 'Success');
                } else {
                    $transactions = $paidTransactions;
                    return ResponseFormatter::success($transactions, 'Success');
                }
            } else {
                $transactions = $unpaidTransactions;
                return ResponseFormatter::success($transactions, 'success');
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

        $request->validate([
            'address' => 'nullable',
            'total_price' => 'required',
            'takeaway_charge' => 'required|min:0',
            'status' => 'required|in:PENDING, SUCCESS, CANCELLED, FAILED, SHIPPING, SHIPPED',
            'payment' => 'required|in:QRIS,MANUAL',
            'point_usage' => 'required|min:0',
            'payment_type' => 'required|in:bayar_sekarang,bayar_nanti',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|min:1',
            'items.*.note' => 'nullable',
        ]);

        try {
            if (!$this->validatecart($request->items)) {
                return response()->json([
                    'message' => 'Checkout failed',
                    'data' => 'Item from different merchant'
                ]);
            } else {
                $transaction = Transaction::create([
                    'users_id' => $user,
                    'address' => $request->address,
                    'total_price' => $request->total_price,
                    'status' => $request->status,
                    'payment' => $request->payment,
                    'point_usage' => $request->point_usage,
                    'payment_type' => $request->payment_type,
                ]);

                foreach ($request->items as $product) {

                    TransactionItem::create([
                        'users_id' => $user,
                        'products_id' => $product['id'],
                        'transactions_id' => $transaction->id,
                        'quantity' => $product['quantity'],
                        'note' => $product['note'],
                    ]);
                }
                return ResponseFormatter::success($transaction->load('items.product'), 'Data list transaksi berhasil diambil');
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Something Happen', 500);
        }
    }



    public function potentialpoint($user, $items, $validatedDataTransaction)
    {
        // $group = User::with('usergroup.group')->where('id',$user)->get();
        //$group = $user;
        if ($user === 'admin') {
            $totalprice = $validatedDataTransaction['total_price'];
            $rules = 20;
            $potentialpoint = $totalprice / $rules;
            return $potentialpoint;
        } elseif ($user === 'member') {
            return "Potential Point 20%";
        } elseif ($user === 'dosen') {
            return "Potential Point 50%";
        }
        return false;
    }

    public function existingpoint($user, $totalprice)
    {
        $totalprice = $totalprice['total_price'];
        $existingpoint = $user['user']['point'];
        if ($existingpoint > 0) {
            $rules = $totalprice / 50;
            return $rules;
        }
        return false;
    }

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



    public function validationusergroups($user)
    {

        $user = User::with('usergroup.group')->where('id', $user)->get();

        $userGroup = collect($user)->pluck('current_team_id');
        if ($userGroup !== 0) {
            return true;
        } else {
            return false;
        }

        return $userGroup;
    }

    public function validationpromoprice($items)
    {
        // $productRequest = $items;
        // foreach ($productRequest as $product) {
        //     $productId[] = $product['products_id'];
        //     $product = Product::where('promo_price', '>', 0)->whereIn('id', $productId)->get();
        // };
        // return $product;

        $productRequest = collect($items)->pluck('products_id')->toArray();
        $product = Product::where('promo_price', '>', 0)->whereIn('id', $productRequest)->get();
        return $product;
    }


    public function detailtransaction()
    {
        $user = Auth::user()->id;
        try {
            if ($user) {

                $transaction = Transaction::with('items.product', 'items.product.merchant')->where('users_id', $user)->orderBy('created_at', 'desc')->first();
                return ResponseFormatter::success($transaction, 'Data transaksi dapat ditemukan');
            } else {
                return ResponseFormatter::error(null, 'Data Transaksi Tidak Ditemukan', 400);
            }
        } catch (Exception $error) {
            return ResponseFormatter::error(
                [
                    'error' => $error,
                    'message' => 'Something Went Wrong',

                ],
                'Authenticated Failed',
                500
            );
        }
    }

    public function payments(Request $request)
    {
        $user = Auth::user()->id;
        $request->validate([
            'total_price' => 'required',
            'payment' => 'required|in:QRIS,MANUAL,CASH',
        ]);
        $transaction = Transaction::where('users_id', $user)->orderBy('created_at', 'desc')->first();
        //Update totalprice and payment method
        $transaction->total_price = $request->total_price;
        $transaction->payment = $request->payment;
        $transaction->save();
        return ResponseFormatter::success($transaction, "success");
    }


    public function confirmpayment(ImageStoreRequest $request, Transaction $transaction)
    {
        $user = Auth::user();
        $request->validated();
        $paymentImage = $request->file('payment_image');
        // Find the user's latest transaction
        $transaction = Transaction::with('items.product.merchant')->where('users_id', $user->id)->latest()->first();
        if ($request->hasFile('payment_image')) {
            $transaction->payment_image = $paymentImage->store('public/transactions');
            $transaction->save();
            return ResponseFormatter::success($transaction, 'success');
        };
    }
}
