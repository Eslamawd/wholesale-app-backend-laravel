<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;


    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/product',[ProductController::class, 'index']);
    Route::get('/product/{id}',[ProductController::class, 'show']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    

    Route::middleware([AdminMiddleware::class])->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('product', ProductController::class);
        Route::apiResource('orders', OrderController::class);
        Route::get('all-order',[ OrderController::class, 'orders']);


        Route::get('/users', [AuthController::class, 'index']);
        Route::get('/users/{id}', [AuthController::class, 'show']);
        Route::put('/users/{id}', [AuthController::class, 'updated']);
        Route::delete('/users/{id}', [AuthController::class, 'destroy']);
        Route::post('/users/{id}/change-role', [AuthController::class, 'changeRole']);

        
        Route::get('/order/count', [OrderController::class, 'count']);
        Route::get('/user/count', [AuthController::class, 'count']);
        Route::get('/revnue/count', [OrderController::class, 'getRevenue']);

    
        Route::post('/wallet/deposit/{id}', [WalletController::class, 'deposit']);
        Route::post('/wallet/withdraw/{id}', [WalletController::class, 'withdraw']);

        

    });

   
});
   


