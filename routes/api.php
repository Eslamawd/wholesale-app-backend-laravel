<?php

use App\Http\Controllers\Admin\ProductSyncController;
use App\Http\Controllers\RenewController;
use App\Http\Controllers\UserSealsController;
use App\Http\Middleware\SealsMiddlware;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/all-req', [CategoryController::class, 'getByAdmin']);

Route::get('/categories/all', [CategoryController::class, 'getAll']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/product',[ProductController::class, 'index']);
Route::get('/product/{id}',[ProductController::class, 'show']);

Route::middleware(['auth:sanctum'])->group(function () {


       Route::get('/email/verify', function () {
        return response()->json([
            'message' => 'Your email address is not verified.'
        ], 403);
    })->name('verification.notice');

     // إعادة إرسال رابط التحقق
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent!']);
    })->name('verification.send');

    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/wallet/balance', [WalletController::class, 'balance']);

    Route::middleware([ 'verified'])->group(function () {

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);

    Route::get('/payment', [PaymentController::class, 'index']);
    Route::post('/payment', [PaymentController::class, 'store']);

    Route::post('/renew', [RenewController::class,'store']);

    Route::post('/subscribe', [SubscriptionController::class, 'store']);
    Route::get('/subscribe', [SubscriptionController::class, 'index']);

    
    Route::middleware([SealsMiddlware::class])->prefix('seals')->group(function () {
    Route::get('user/sub', [UserSealsController::class, 'index']);
    Route::post('user/sub', [UserSealsController::class, 'store']);
    Route::get('all/user/sub', [UserSealsController::class, 'getAllUserBySealer']);
    Route::post('new/sub/{id}', [UserSealsController::class, 'createNewSub']);
   
    });

    Route::middleware([AdminMiddleware::class])->prefix('admin')->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('product', ProductController::class);
        Route::get('/all/product', [ProductController::class, 'getByAdmin']);
        Route::apiResource('orders', OrderController::class);
        Route::get('all-order',[ OrderController::class, 'orders']);

        
        
        Route::get('/renew', [RenewController::class,'index']);
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
        
        Route::post('/payment/{id}', [PaymentController::class, 'addBalance']);
        Route::get('/payment', [PaymentController::class, 'getAllPay']);

        
        Route::get('/subscribe', [SubscriptionController::class, 'admin']);
        Route::get('/subscribe/revnue', [SubscriptionController::class, 'getRevenue']);
        Route::get('/subscribe/count', [AuthController::class, 'count']);

        Route::post('/subscribe/{id}', [SubscriptionController::class, 'changeStatus']);
        
        

        Route::post('/account', [AccountController::class, 'store']);
        Route::put('/account/{id}', [AccountController::class, 'update']);
        Route::delete('/account/{id}', [AccountController::class, 'destroy']);


        
        
       Route::post('/sync-products', [ProductSyncController::class, 'syncProdacts']);
        

    });

});

   
});
   



Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::find($id);

    if (! $user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    // تحقق من صحة الـ hash
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Invalid verification link.'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.'], 200);
    }

    $user->markEmailAsVerified();

    return response()->json(['message' => 'Email verified successfully.'], 200);
})->name('verification.verify');



Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
