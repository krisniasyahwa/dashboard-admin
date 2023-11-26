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
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\validation\Validator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseFormatSame;
use Throwable;

class TransactionController extends Controller
{
    use FilterByDate;

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

     /* Method Helper*/
    /* Helper For Validation*/
    public function validationHelper(Request $request){
        $items = $request->items;
        $idItems = array_column($items, 'id');
        $idFirstItem = $items[0]['id'];
        $merchantId = Product::where('id', $idFirstItem)->pluck('merchants_id');
        $merchantData = Merchant::where('id', $merchantId)->get();
        $products = Product::with(['featured_image','galleries', 'category', 'merchant'])->whereIn('id', $idItems)->get();
        $transaction = $request->transaction_type;


        //Organize Product Data
        $productData = $products->map(function ($product, $index) use ($items) {
            $quantity = $items[$index]['quantity'];
            $note = $items[$index]['note'] ?? ''; // Use the note from the request
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
                'galleries' => $product->galleries,
                // 'featured_image' => $product->featured_image,
            ];
        });

        //Organize Transaction Summary Data
        if ($transaction === 'DINE_IN') {
            $subtotal = 0;
            
            foreach ($productData as $product) {
                $quantity = $product['quantity'];
                $price = ($product['promo_price'] <= $product['price']) ? $product['promo_price'] : $product['price'];
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
                $quantity = $product['quantity'];
                $price = ($product['promo_price'] <= $product['price']) ? $product['promo_price'] : $product['price'];
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

        $result = [
            'merchant' => $merchantData[0],
            'items' => $productData,
            'Summary' => $summaryData,
        ];
        return $result;
    }

    /* Helper For Detail Transaction */
    public function getDetailTransactionById($id){
        return Transaction::with('items.product.merchant')->where('id', $id)->first();
    }
    
    /* Method For GET Histories Transactions */
    public function resultHistories($transaction){
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
                    foreach($transactions as $transaction){
                        $result[] = $this->resultHistories($transaction);
                      }
                    return ResponseFormatter::success($result, 'Success');
                } else {
                    $transactions = $paidTransactions;
                    foreach($transactions as $transaction){
                        $result[] = $this->resultHistories($transaction);
                    }
                    return ResponseFormatter::success($result, 'Success');
                }
            } else {
                $transactions = $unpaidTransactions;              
                foreach($transactions as $transaction){
                  $result[] = $this->resultHistories($transaction);
                }
                return ResponseFormatter::success($result, 'success');
            }
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th, 'Something Happen', 500);
        }
    }

    //Function for handle API validation product after cart  
    public function validation(Request $request)
    {
        try {
            $request->validate([
                'transaction_type' => 'required|in:DINE_IN,TAKEAWAY',
                'items' => 'required|array',
                'items.*.id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|min:1',
                'items.*.note' => 'nullable'
            ]);
            $result = $this->validationHelper($request);
            return ResponseFormatter::success($result, 'Transactions Validated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error,
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

        try{
            $user = Auth::user();
            $paymentImage = $request->file('payment_image');
            $transaction = Transaction::where('users_id', $user->id)->where('id', $id)->first();

            if (!$paymentImage) {
                return ResponseFormatter::error(null, 'payment_image Not Found', 400);
            }

            if ($transaction && $transaction->payment_status === 'EXPIRED'){
                return ResponseFormatter::error(null, 'Transaction Expired', 400);
            }

            // If transaction status is pending, check if transaction is expired or not
            if ($transaction && $transaction->payment_type === 'BAYAR_SEKARANG' && $transaction->status === 'PENDING') {
                $now = Carbon::now();
                $expired = $transaction->created_at->addMinutes(15);

                if ($now->greaterThan($expired)) {
                    $transaction->status = 'EXPIRED';
                    $transaction->save();
                    return ResponseFormatter::error(null, 'Transaction Expired', 400);
                }
            } else if ($transaction) {
                $transaction->payment_image = $paymentImage->store('public/transactions');
                $transaction->status_payment = 'REVIEW';
                $transaction->save();
                return ResponseFormatter::success($transaction, 'Payment Image Uploaded');
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
            $transactions = Transaction::with('merchant')->where('users_id', $user->id)->where('id', $id)->first();

            if ($transactions && $transactions->status === 'PENDING' && $transactions->status_payment === 'UNPAID' && $transactions->payment_type === 'BAYAR_SEKARANG') {
                $result = [
                    'total_price' => $transactions->total_price,
                    'qr' => $transactions->merchant->qris_path,
                    'created' => $transactions->created_at,
                    'expired' => $transactions->created_at->addMinutes(15),
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

    public function detailTransaction($idTransaction){
        try{
            $transaction= $this->getDetailTransactionById($idTransaction);
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
        }catch(Exception $error){
            return ResponseFormatter::error([
                            'message' => 'Semething Happened',
                            'error' => $error,
                            'code' => 500
                        ]);
        }
    }

    public function history($idTransaction){
        // $id = $request->route('id');
        try{
            $transaction = $this->getDetailTransactionById($idTransaction);
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

        }catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Semething Happened',
                'error' => $error,
                'code' => 500
            ]);
        }
    }


    public function pass($idTransaction){
        try{
            $transaction = $this->getDetailTransactionById($idTransaction);
            return ResponseFormatter::success($transaction, 'Success');
        }catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something Happened',
                'error' => $error->getMessage(),
                'code' => 500
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
            $transactions = Transaction::with('merchant')->with('items.product')->where('users_id', $user->id)->get();

            if($transactions->isEmpty()){
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
                    $transaction->status = 'EXPIRED';
                    $transaction->save();
                    return ResponseFormatter::error(null, 'Transaction Expired', 400);
                } else {
                    $transaction->expired_at = $expired;
                    return ResponseFormatter::success($transaction, 'Success');
                }
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
     public function store(Request $request)
     {
         $user = Auth::user();
         $request->validate([
             'transaction_type' => 'required|in:DINE_IN,TAKEAWAY',
             'payment_type' => 'required|in:BAYAR_SEKARANG,BAYAR_DITEMPAT',
             'payment' => 'required|in:QRIS,CASH',
             'items' => 'required|array',
             'items.*.id' => 'required|exists:products,id',
             'items.*.quantity' => 'required|min:1',
             'items.*.note' => 'nullable',
         ]);
 
         try {  
             $transactionValidated = $this->validationHelper($request);
           
             switch ($request->transaction_type) {
                 case 'TAKEAWAY':
                     $transaction = Transaction::create([
                         'merchants_id' => $transactionValidated['merchant']['id'],
                         'users_id' => $user->id,
                         'transaction_type' => $request->transaction_type,
                         'takeaway_charge' => $transactionValidated['Summary']['takeaway_charge'],
                         'total_price' => $transactionValidated['Summary']['total'],
                         'status' => 'PENDING',
                         'payment' => $request->payment,
                         'payment_type' => $request->payment_type
                     ]);
 
                     foreach($request->items as $item){
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
                         'transaction_type' => $request->transaction_type,
                         'takeaway_charge' => 0,
                         'total_price' => $transactionValidated['Summary']['total'],
                         'status' => 'PENDING',
                         'payment' => $request->payment,
                         'payment_type' => $request->payment_type
                     ]);
                     foreach($request->items as $item){
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
         } catch (\Throwable $th) {
             return ResponseFormatter::error($th, 'Something Happen', 500);
         }
     }

}
