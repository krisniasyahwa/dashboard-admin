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

//Auth:sanctum is a middleware to check if user is login or not 
Route::middleware(['auth:sanctum'])->group(function () { 
    Route::get('user', [UserController::class, 'fetch']); 
    Route::post('user', [UserController::class, 'updateProfile']); 
    Route::post('logout', [UserController::class, 'logout']); 

    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::post('transactions/validation', [TransactionController::class, 'validation']);
    Route::get('transactions/{id}', [TransactionController::class, 'show']);
    Route::get('transactions/{id}/payment', [TransactionController::class, 'paymentInformation']);
    Route::post('transactions/{id}/payment', [TransactionController::class, 'paymentConfirmation']);

    Route::post('merchants', [MerchantController::class, 'store']);
    Route::delete('merchants/{slug}', [MerchantController::class, 'destroy']);
    Route::get('products/restore', [ProductController::class, 'restore']);
});


Route::get('merchants', [MerchantController::class, 'index']);
Route::get('merchants/{id}', [MerchantController::class, 'show']);
Route::get('merchants/{id}/categories', [MerchantController::class, 'categories']);
Route::get('merchants/{id}/products', [MerchantController::class, 'products']);

Route::get('products', [ProductController::class, 'all']);
Route::get('products/bestseller', [ProductController::class, 'bestSeller']);
Route::get('products/random', [ProductController::class, 'randomProducts']);
Route::get('products/featureImage', [ProductController::class, 'featureImage']);
Route::get('categories', [ProductCategoryController::class, 'all']);

Route::post('login', [UserController::class, 'login']); 
Route::post('register', [UserController::class, 'register']);
