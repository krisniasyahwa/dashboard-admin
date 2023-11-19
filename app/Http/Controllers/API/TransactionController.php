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

    //Function for handle API get all history user transaction
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

    //Function for handle API checkout transaction after validation transaction
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function checkout(Request $request)
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

    //Function for 
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

    //Function for handle API confirmation payment with upload QR Image
    public function confirmpayment(Request $request)
    {
        try{
            $id = $request->route('id');
        $request->validate([
            'payment_image' => 'required|image|mimes:jpeg,jpg,png,svg|max:2048'
        ]);
        $paymentImage = $request->file('payment_image');
        $transaction = Transaction::with('items.product.merchant')->where('id', $id)->first();
        if ($request->hasFile('payment_image')) {
            $transaction->payment_image = $paymentImage->store('public/transactions');
            $transaction->save();
        };
        return ResponseFormatter::success($transaction,'success');
        }catch(\Throwable $th){
            return ResponseFormatter::error($th, 'Something Happen', 500 );
        }
        
    }

    //Function for handle API confirmation transaction before payment
    public function confirmation(Request $request)
    {
        $user = Auth::user();
        $id = $request->route('id');
        try {
            $transactions = Transaction::with('items.product.merchant')->where('id', $id)->first();
            if (!empty($transactions)) {
                $merchantQR = $transactions['items']['0']['product']['merchant']['qris_path'];
                $totalPrice = $transactions['total_price'];
                $createdAt = $transactions->created_at->format('H:i:s');
                // Parse the 'H:i:s' string into a DateTime object
                $createdAtDateTime = Carbon::createFromFormat('H:i:s', $createdAt);
                // Add 15 minutes to the DateTime object
                $createdAtDateTime->addMinutes(15);
                $expiredAt = $createdAtDateTime->format('H:i:s');
                $expiredAtDateTime = Carbon::createFromFormat('H:i:s', $expiredAt);
                $result = [
                    'total_price' => $totalPrice,
                    'qr' => $merchantQR,
                    'created' => $createdAtDateTime,
                    'expired' => $expiredAtDateTime->addMinutes(15)
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

    // Function for validation transaction
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
                // 'merchant_id' => $product->merchant,
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

// START API DEVELOPMENTS v2
    // public function validationcart(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'items' => 'required|array',
    //             'items.*.id' => 'required|exists:products,id',
    //             'items.*.quantity' => 'required|min:1',
    //             'items.*.note' => 'nullable'
    //         ]);

}
