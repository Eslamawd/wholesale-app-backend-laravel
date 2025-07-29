<?php

use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;





    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

   Route::middleware(['auth:sanctum'])->group(function () {
    // شاشة التنبيه: لم يتم التحقق
    Route::get('/email/verify', function () {
        return response()->json([
            'message' => 'Your email address is not verified.'
        ], 403);
    })->name('verification.notice');

    // رابط التحقق
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return response()->json(['message' => 'Email verified successfully.']);
    })->middleware(['signed'])->name('verification.verify');

    // إعادة إرسال رابط التحقق
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent!']);
    })->name('verification.send');
});






