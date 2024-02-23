<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Merchant;
use App\Traits\FilterByDate;
use App\Services\ProductService;
use App\Services\TransactionService;
use App\Http\Requests\API\ValidationRequest;
use App\Http\Requests\API\TransactionRequest;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\validation\Validator;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\ElseIf_;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseFormatSame;
use Throwable;

class TransactionController extends Controller
{
    use FilterByDate;

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /* Helper For Validation*/
    public function validationHelper($requestValidated)
    {
        $itemsData = $requestValidated['items'];
        $itemsId = array_column($itemsData, 'id');
        $service = new ProductService;
        $stocks = $service->stockValidation($itemsData);
        if ($stocks === false) {
            $result = $stocks;
            return $result;
        } else {
            $itemFirstId = $itemsData[0]['id'];
            $merchantId = Product::where('id', $itemFirstId)->pluck('merchants_id')->first();
            $merchantData = Merchant::where('id', $merchantId)->first();
            $products = Product::with(['galleries', 'category', 'merchant'])->whereIn('id', $itemsId)->get();
            $transactionType = $requestValidated['transaction_type'];
            $productData = $products->map(function ($product, $index) use ($itemsData) {
                $quantity = $itemsData[$index]['quantity'];
                $note = $itemsData[$index]['note'] ?? '';
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'tags' => $product->tags,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'promo_price' => $product->promo_price,
                    'takeaway_charge' => $product->takeway_charge,
                    'note' => $note,
                    'merchant' => $product->merchant,
                    'category' => $product->category,
                    'galleries' => $product->galleries
                ];
            });

            if ($transactionType === 'DINE_IN') {
                $subtotal = 0;
                foreach ($productData as $product) {
                    $productPrice = ($product['promo_price'] > 0 && $product['promo_price'] <= $product['price']) ? $product['promo_price'] : $product['price'];
                    $quantity = $product['quantity'];
                    $price = $productPrice;
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
                $total_takeaway_charge = 0;
                foreach ($productData as $product) {
                    $productPrice = ($product['promo_price'] > 0 && $product['promo_price'] <= $product['price']) ? $product['promo_price'] : $product['price'];

                    $quantity = $product['quantity'];
                    $price = $productPrice;
                    $takeaway_charge = $product['takeaway_charge'];
                    $calculation = $quantity * $price;
                    $takeaway = $quantity * $takeaway_charge;
                    $subtotal += $calculation;
                    $total_takeaway_charge += $takeaway;
                }
                $summaryData = [
                    'subtotal' => $subtotal,
                    'takeaway_charge' => $total_takeaway_charge,
                    'admin_fee' => 0,
                    'total' => $subtotal + $total_takeaway_charge
                ];
            }
        }
        $result = [
            'merchant' => $merchantData,
            'items' => $productData,
            'Summary' => $summaryData,
        ];
        return $result;
    }

    //Function for handle API validation product after cart  
    public function validation(ValidationRequest $request)
    {
        try {
            $requestValidated = $request->validated();
            $result = $this->validationHelper($requestValidated);
            if (!$result) {
                return ResponseFormatter::error($result, 'Stock Product Not Available', 404);
            }
            return ResponseFormatter::success($result, 'Transactions Validated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error->getMessage(),
                'code' => 500,
            ]);
        }
    }


    //Function for handle API confirmation payment with upload QR Image
    public function paymentConfirmation($id, Request $request)
    {
        $request->validate([
            'payment_image' => 'required|image|mimes:jpeg,jpg,png,svg'
        ]);

        try {
            $user = Auth::user();
            $paymentImage = $request->file('payment_image');
            $transaction = Transaction::where('users_id', $user->id)->where('id', $id)->first();

            $isPaymentUnpaidOrRejected = $transaction->status_payment === 'UNPAID' || $transaction->status_payment === 'REJECTED';

            if (!$paymentImage) {
                return ResponseFormatter::error(null, 'payment_image Not Found', 400);
            }

            if ($transaction && $transaction->status === 'EXPIRED') {
                return ResponseFormatter::error(null, 'Transaction Expired', 400);
            }

            // If transaction status is pending, check if transaction is expired or not
            if ($transaction && $transaction->payment_type === 'BAYAR_SEKARANG' && $isPaymentUnpaidOrRejected) {
                $now = Carbon::now();
                $expired = $transaction->created_at->addMinutes(15);

                if ($now->greaterThan($expired)) {
                    $transaction->status_payment = 'EXPIRED';
                    $transaction->save();
                    return ResponseFormatter::error(null, 'Transaction Expired', 400);
                } else {
                    $transaction->payment_image = $paymentImage->store('public/transactions');
                    $transaction->status_payment = 'REVIEW';
                    $transaction->save();
                    return ResponseFormatter::success($transaction, 'Payment Image Uploaded');
                }
            } else {
                return ResponseFormatter::error(null, 'Transaction Not Found', 404);
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error,
                'code' => 500,
            ]);
        }
    }

    //Function for handle API confirmation transaction before payment
    public function paymentInformation($id)
    {
        try {
            $user = Auth::user();
            $transaction = Transaction::with('merchant')->where('users_id', $user->id)->where('id', $id)->first();

            $isPaymentUnpaidOrRejected = $transaction->status_payment === 'UNPAID' || $transaction->status_payment === 'REJECTED';

            if ($transaction && $transaction->status === 'PENDING' && $isPaymentUnpaidOrRejected && $transaction->payment_type === 'BAYAR_SEKARANG') {
                $result = [
                    'total_price' => $transaction->total_price,
                    'qr' => $transaction->merchant->qris_path,
                    'created' => $transaction->created_at,
                    'expired' => $transaction->created_at->addMinutes(15),
                ];
                return ResponseFormatter::success($result, 'Data Found');
            } else {
                return ResponseFormatter::error(null, 'Transaction Not Found', 404);
            }
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error,
                'code' => 500,
            ]);
        }
    }
    /**
     * Display a listing of the transaction.
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        try {
            $user = Auth::user();
            $service = new TransactionService;
            $transactions = $service->history($user->id);

            if ($transactions->isEmpty()) {
                return ResponseFormatter::error(null, 'Transaction Not Found', 404);
            }

            return ResponseFormatter::success($transactions, 'Success');
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Something Happen', 500);
        }
    }

    /**
     * Display the specified transaction.
     * @param $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        try {
            $user = Auth::user();
            $transaction = Transaction::with('merchant')->with('items.product')->where('users_id', $user->id)->where('id', $id)->first();

            // If transaction status is pending, check if transaction is expired or not
            if ($transaction && $transaction->status_payment === 'UNPAID') {
                $now = Carbon::now();
                $expired = $transaction->created_at->addMinutes(15);

                if ($now->greaterThan($expired)) {
                    $transaction->status_payment = 'EXPIRED';
                    $transaction->save();
                }

                $transaction->expired_at = $expired;
                return ResponseFormatter::success($transaction, 'Success');
            } else if ($transaction) {
                return ResponseFormatter::success($transaction, 'Success');
            } else {
                return ResponseFormatter::error(null, 'Transaction Not Found', 404);
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Something Happen', 500);
        }
    }

    //Function for handle API checkout transaction after validation transaction
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TransactionRequest $request, ProductService $productService)
    {
        $user = Auth::user();
        $requestValidated = $request->validated(); 

        try {
            $transactionValidated = $this->validationHelper($requestValidated);
            $transactionType = $requestValidated['transaction_type'];
            $paymentType = $requestValidated['payment_type'];
            $payment = $requestValidated['payment'];
            if (!$transactionValidated) {
                return ResponseFormatter::error($transactionValidated, 'Stock Product Not Available', 404);
            }
            switch ($transactionType) {
                case 'TAKEAWAY':
                    $transaction = Transaction::create([
                        'merchants_id' => $transactionValidated['merchant']['id'],
                        'users_id' => $user->id,
                        'transaction_type' => $transactionType,
                        'takeaway_charge' => $transactionValidated['Summary']['takeaway_charge'],
                        'total_price' => $transactionValidated['Summary']['total'],
                        'status' => 'PENDING',
                        'payment' => $payment,
                        'payment_type' => $paymentType
                    ]);

                    $productService->stockDecrement($request->items);

                    foreach ($request->items as $item) {
                        TransactionItem::create([
                            'users_id' => $user->id,
                            'products_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'transactions_id' => $transaction->id,
                            'note' => $item['note']
                        ]);
                    }
                    return ResponseFormatter::success($transaction->load('items.product'), 'Checkout Berhasil');
                    break;
                case 'DINE_IN':
                    $transaction = Transaction::create([
                        'merchants_id' => $transactionValidated['merchant']['id'],
                        'users_id' => $user->id,
                        'transaction_type' => $transactionType,
                        'takeaway_charge' => 0,
                        'total_price' => $transactionValidated['Summary']['total'],
                        'status' => 'PENDING',
                        'payment' => $payment,
                        'payment_type' => $paymentType
                    ]);
                    $productService->stockDecrement($request->items);
                    foreach ($request->items as $item) {
                        TransactionItem::create([
                            'users_id' => $user->id,
                            'products_id' => $item['id'],
                            'quantity' => $item['quantity'],
                            'transactions_id' => $transaction->id,
                            'note' => $item['note']
                        ]);
                    }
                    return ResponseFormatter::success($transaction->load('items.product'), 'Checkout Berhasil');
                    break;
                default:
                    return ResponseFormatter::error(null, 'Transaction Type Not Found', 400);
                    break;
            }
            return ResponseFormatter::success($transactionType, "Success");
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Something Happen', 500);
        }
    }

     /* Method For GET Histories Transactions */
     public function resultHistories($transaction)
     {
         $merchant = $transaction->items->first()->product->merchant;
         $sumQuantity = $transaction->items->sum('quantity');
         return [
             'id' => $transaction->id,
             'status' => $transaction->status,
             'status_payment' => $transaction->status_payment,
             'merchant' => $merchant,
             'sum_quantity' => $sumQuantity,
             'items' => $transaction->items,
             'total_price' => $transaction->total_price
         ];
     }
 
     public function histories(Request $request)
     {
         $user = Auth::user();
         try {
             $unpaidTransactions = Transaction::with('items.product.merchant')->where('users_id', $user->id)->where('status_payment', 'UNPAID')->orderBy('created_at', 'desc')->get();
             $paidTransactions = Transaction::with('items.product.merchant')->where('users_id', $user->id)->where('status_payment', 'PAID')->orderBy('created_at', 'desc')->get();
 
             if ($unpaidTransactions->isEmpty() && $paidTransactions->isEmpty()) {
                 return ResponseFormatter::error(null, 'Transactions Not Found', 400);
             } elseif ($paidTransactions->isNotEmpty()) {
                 if ($unpaidTransactions->isNotEmpty()) {
                     $transactions = $unpaidTransactions->concat($paidTransactions);
                     foreach ($transactions as $transaction) {
                         $result[] = $this->resultHistories($transaction);
                     }
                     return ResponseFormatter::success($result, 'Success');
                 } else {
                     $transactions = $paidTransactions;
                     foreach ($transactions as $transaction) {
                         $result[] = $this->resultHistories($transaction);
                     }
                     return ResponseFormatter::success($result, 'Success');
                 }
             } else {
                 $transactions = $unpaidTransactions;
                 foreach ($transactions as $transaction) {
                     $result[] = $this->resultHistories($transaction);
                 }
                 return ResponseFormatter::success($result, 'success');
             }
         } catch (\Throwable $th) {
             return ResponseFormatter::error($th, 'Something Happen', 500);
         }
     }


    public function detailTransaction($id)
    {
        try {
            $service = new TransactionService;
            $transaction = $service->detailTransaction($id);
            $expiredTime = $transaction->created_at->addMinutes(15);

            $summary = [
                'id_transaction' => $transaction->id,
                'time' => $transaction->created_at->format('H:i:s'),
                'date' => $transaction->created_at->format('Y-m-d'),
                'method' => $transaction->payment,
                'admin_fee' => 0,
                'subtotal' => $transaction->total_price - $transaction->takeaway_charge,
                'takeaway_charge' => $transaction->takeaway_charge,
                'total_price' => $transaction->total_price,

            ];

            //Response Output
            $result = [
                'expired_time' => $expiredTime,
                'status' => $transaction->status,
                'merchant' => $transaction->items[0]->product->merchant,
                'items' => $transaction->items,
                'summary' => $summary
            ];
            return ResponseFormatter::success($result, "History Transaction untuk Id {$transaction->id} berhasil ditemukan");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Semething Happened',
                'error' => $error,
                'code' => 500
            ]);
        }
    }

    public function history($id)
    {
        try {
            $service = new TransactionService;
            $transaction = $service->detailTransaction($id);
            $summary = [
                'id_transaction' => $transaction->id,
                'time' => $transaction->created_at->format('H:i:s'),
                'date' => $transaction->created_at->format('Y-m-d'),
                'payment' => $transaction->payment,
                'status_payment' => $transaction->status_payment,
                'subtotal' => $transaction->total_price - $transaction->takeaway_charge,
                'takeaway_charge' => $transaction->takeaway_charge,
                'total_price' => $transaction->total_price,

            ];
            $result = [
                'transaction_type' => $transaction->transaction_type,
                'items' => $transaction->items,
                'Summary' => $summary

            ];
            return ResponseFormatter::success($result, "History Transaction untuk Id {$transaction->id} berhasil ditemukan");
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Semething Happened',
                'error' => $error,
                'code' => 500
            ]);
        }
    }


    public function pass($id)
    {
        try {
            $service = new TransactionService;
            $transaction = $service->detailTransaction($id);
            return ResponseFormatter::success($transaction, 'Success');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error->getMessage(),
                'code' => 500
            ]);
        }
    }
    /**
     * *BETA Method For listing expired transactions
     */
    // public function test(Request $request){
    //     $user = Auth::user();
    //     $id = $request->input('id');
    //     $transaction = Transaction::with('items.product.merchant')->where('users_id', $user->id)->where('status','EXPIRED')->get();
    //     $items = $transaction[0]['items'];
    //     $result = $this->stockIncrement($items);
    //     return ResponseFormatter::success($result, 'Success');
    // }
}
