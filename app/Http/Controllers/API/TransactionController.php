<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\Product;
use App\Models\Merchant;
use App\Traits\FilterByDate;
use App\Http\Requests\ImageStoreRequest;
use App\Models\Cart;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseFormatSame;

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
            'items.*.id' => 'required|existroducts,id',
            'items.*.quantity' => 'required|min:1',
            'items.*.note' => 'nullable',
        ]);

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

    public function validation(Request $request)
    {
        try {
            $request->validate([
                'transaction_type' => 'required|in:dine_in,takeaway',
                'items' => 'required|array',
                'items.*.id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|min:1',
                'items.*.note' => 'nullable'
            ]);

            $items = $request->items;
            $idItems = array_column($items, 'id'); // Extract all product IDs
            $merchantId = Product::whereIn('id', $idItems)->pluck('merchants_id');
            $merchants = Merchant::whereIn('id', $merchantId)->get();
            $products = Product::whereIn('id', $idItems)->get();
            $transaction = $request->transaction_type;



            // Organize merchant data
            $merchantData = $merchants->map(function ($merchants) {
                return [
                    'id' => $merchants->id,
                    'name' => $merchants->name,
                    'address' => $merchants->address,
                ];
            });
            //Organisze Product Data
            $productData = $products->map(function ($product) use ($items) {
                $item = collect($items)->first(function ($item) use ($product) {
                    return $item['id'] == $product->id;
                });
                $quantity = isset($item['quantity']) ? $item['quantity'] : 0;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'promo_price' => $product->promo_price,
                ];
            });
            //Organize Transaction Summary Data
            if ($transaction === 'dine_in') {
                $subtotal = 0;
                foreach ($productData as $product) {
                    $quantity = $product['quantity'];
                    $price = $product['price'];
                    $calculation = $quantity * $price;
                    $subtotal += $calculation;
                }

                $summaryData = [
                    'subtotal' => $subtotal,
                    'takeaway_price' => 0,
                    'admin_fee' => 0,
                    'total' => $subtotal
                ];
            } else {
                $subtotal = 0;
                $takeaway_charge = 2000;
                foreach ($productData as $product) {
                    $quantity = $product['quantity'];
                    $price = $product['price'];
                    $calculation = $quantity * $price;
                    $subtotal += $calculation;
                }
                $summaryData = [
                    'subtotal' => $subtotal,
                    'takeaway_charge' => $takeaway_charge,
                    'admin_fee' => 0,
                    'total' => $subtotal + $takeaway_charge
                ];
            }


            $result = [
                'merchant' => $merchantData,
                'items' => $productData,
                'Summary' => $summaryData,

            ];

            return ResponseFormatter::success($result, 'Transactions Validated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error,
                'code' => 500,
            ]);
        }
    }



    public function potentialpoint($user, $items, $validatedDataTransaction)
    {
      
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

        $productRequest = collect($items)->pluck('products_id')->toArray();
        $product = Product::where('promo_price', '>', 0)->whereIn('id', $productRequest)->get();
        return $product;
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

    public function confirmation(Request $request)
    {
        $user = Auth::user()->id;
        $id = $request->route('id');
        try {
            //$transactions = Transaction::findOrFail($id);
            $transactions = Transaction::with('items.product.merchant')->where('id', $id)->get();
            if (!empty($transactions)) {
                $merchantQR = $transactions[0]['items']['0']['product']['merchant']['qris_path'];
                $totalPrice = $transactions[0]['total_price'];
                $createdAt = $transactions[0]->created_at->format('H:i:s');
                // Parse the 'H:i:s' string into a DateTime object
                $createdAtDateTime = Carbon::createFromFormat('H:i:s', $createdAt);
                // Add 15 minutes to the DateTime object
                $createdAtDateTime->addMinutes(15);
                $expiredAt = $createdAtDateTime->format('H:i:s');
                $result = [
                    'total_price' => $totalPrice,
                    'qr' => $merchantQR,
                    'created' => $createdAt,
                    'expired' => $expiredAt
                ];
                return ResponseFormatter::success($result, 'Data Found');
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error,
                'code' => 500,
            ]);
        }
    }
}
