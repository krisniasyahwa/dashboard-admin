<?php

use App\Http\Controllers\API\MerchantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\ProductCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('user', [UserController::class, 'fetch']);
    Route::post('user', [UserController::class, 'updateProfile']);
    Route::post('logout', [UserController::class, 'logout']);

    Route::get('transactions', [TransactionController::class, 'all']);
    Route::post('checkout', [TransactionController::class, 'checkout']);

    Route::post('merchants', [MerchantController::class, 'store']);
    Route::delete('merchants/{slug}', [MerchantController::class, 'destroy']);
});


Route::get('merchants', [MerchantController::class, 'index']);
Route::get('merchants/{id}', [MerchantController::class, 'show']);
Route::get('merchants/{id}/categories', [MerchantController::class, 'categories']);
Route::get('merchants/{id}/products', [MerchantController::class, 'products']);

Route::get('products', [ProductController::class, 'all']);
Route::get('products/favorite', [ProductController::class, 'favorites']);
Route::get('categories', [ProductCategoryController::class, 'all']);

Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);
