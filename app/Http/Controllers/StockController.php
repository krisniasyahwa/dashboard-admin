<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $query = Transaction::with(['items','merchant','user'])
                ->whereIn('status_payment',['EXPIRED','REJECTED']);

            $response =  DataTables::of($query)
            ->addColumn('action', function ($item) {
                $column = '
                <a class="inline-block border border-gray-700 bg-gray-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-gray-800 focus:outline-none focus:shadow-outline"
                    href="' . route('dashboard.transaction.show', $item->id) . '">
                    View
                </a>
                ';
                if ($item->stock_rollback_at == null) {
                    $column = $column . '
                    <form class="inline-block" action="' . route('dashboard.stock.rollback', $item->id) . '" method="POST">
                    <button class="border border-blue-700 bg-blue-700 text-white rounded-md px-2 py-1 m-1 transition duration-500 ease select-none hover:bg-blue-800 focus:outline-none focus:shadow-outline" >
                        Rollback
                    </button>
                        ' . method_field('PATCH') . csrf_field() . '
                    </form>
                    ';
                }
                return $column;
            })
            ->rawColumns(['action'])
            ->make();
            return $response;
    }

        $data = ['needs_rollbacked' => Transaction::with(['items','items.product'])->whereIn('status_payment',['EXPIRED','REJECTED'])->whereNull('stock_rollback_at')->count()];
        return view('pages.dashboard.stock.index',compact('data'));
    }

    /**
     * Rollback the transaction and update product stock levels.
     *
     * @param  \App\Models\Transaction  $transaction The transaction to rollback
     * @param  \Illuminate\Support\MessageBag  $errors The error bag to store error messages
     * @return int The number of rows affected by the rollback
     */
    private static function rollback(Transaction $transaction, MessageBag $errors) : int {
        $rowsAffected = 0;

        try {
            foreach ($transaction->items as $item) {
                $rollbackedStock = $item->product->stock + $item->quantity;
                $item->product->stock = $rollbackedStock;
                $item->product->save();
                $rowsAffected++;
            }

            $transaction->stock_rollback_at = now();
            $transaction->save();
        } catch (Exception $e) {
            // Log or handle the exception accordingly
            $errors->add("Rollback failed for transaction ID $transaction->id", $e->getMessage());
            Log::error("Rollback failed for transaction ID $transaction->id: " . $e->getMessage());
        }

        return $rowsAffected;
    }

    /**
     * Rollback a transaction by its ID.
     *
     * @param  \Illuminate\Http\Request  $request The HTTP request instance
     * @param  int|string  $id The ID of the transaction to rollback
     * @return \Illuminate\Http\RedirectResponse The redirect response
     */
    public function rollbackById(Request $request, int|string $id) : RedirectResponse {
        DB::beginTransaction();
        // initiate MassageBag Errors
        $errors = new MessageBag();

        try {
            // Retrieve the transaction with its items and related products
            $transaction = Transaction::with('items.product')->findOrFail($id);

            if ($transaction->stock_rollback_at !== null) {
                throw new Exception("Transaction with ID $id is already rollbacked at $transaction->stock_rollback_at");
            }

            $rowsAffected = StockController::rollback($transaction,$errors);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            // Handle the exception, log or respond accordingly
            $errors->add('Rollback failed:',$e->getMessage());
            Log::error('Rollback failed: ' . $e->getMessage());
            return redirect()->route('dashboard.stock.index')->withErrors($errors);
        }

        // Construct the response data
        $rollbackedIds = http_build_query([$id]);

        return redirect()->route('dashboard.stock.index', ['rollbackedIds' => $rollbackedIds]);
    }

    /**
     * Rollback all eligible transactions.
     *
     * @param  \Illuminate\Http\Request  $request The HTTP request instance
     * @return \Illuminate\Http\RedirectResponse The redirect response
     */
    public function rollbackAll(Request $request) : RedirectResponse {
        DB::beginTransaction();
        $errors = new MessageBag();

        try {
            // Retrieve the transaction with its items and related products
            $transactions = Transaction::with('items.product')
                ->whereIn('status_payment', ['EXPIRED', 'REJECTED'])
                ->whereNull('stock_rollback_at')
                ->get();

            if ($transactions->isEmpty()) {
                throw new Exception("All transactions are already rollbacked");
            }

            $affected = $transactions->map(function ($transaction) use ($errors) {
                $rowsAffected = StockController::rollback($transaction, $errors);
                return ['rowsAffected' => $rowsAffected, 'id' => $transaction->id];
            });

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            // Handle the exception, log or respond accordingly

            $errors->add('Rollback failed:',$e->getMessage());
            Log::error('Rollback failed: ' . $e->getMessage());
            return redirect()->route('dashboard.stock.index')->withErrors($errors);
        }

        // Construct the response data
        $rollbackedIds = http_build_query(array(...$affected->pluck('id')));
        return redirect()->route('dashboard.stock.index',['rollbackedIds' => $rollbackedIds]);
    }
}
