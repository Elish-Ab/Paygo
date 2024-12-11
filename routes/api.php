<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\EnsureUserIsOwner;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\TransactionController;


Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');





Route::post('/user', [UserController::class, 'create_user']);

Route::post('/initialize-payment', [PaymentLinkController::class, 'initializePayment'])->name('initalize');
Route::post('/generate-link', [PaymentLinkController::class, 'generateLink'])->name('generate');


Route::get('/login', function () {
    return response()->json(['message' => 'Use POST to access this route.'], 405);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return "hi";
    });


    Route::get('user/{id}', [WalletController::class, 'check_balance'])->middleware(EnsureUserIsOwner::class);
    Route::post('/transfer', [TransactionController::class, 'transfer'])->middleware(EnsureUserIsOwner::class);

    // Route::post('/pay', 'App\Http\Controllers\ChapaController@initialize')->name('pay');



    // The callback url after a payment
    Route::get('callback/{reference}', 'App\Http\Controllers\ChapaController@callback')->name('callback');
});


Route::post('/payment/initialize', [PaymentLinkController::class, 'initializePayment'])->name('payment.initialize');
Route::get('/payment/callback', [PaymentLinkController::class, 'callback'])->name('payment.callback');
Route::get('/payment/return', [PaymentLinkController::class, 'return'])->name('payment.return');
Route::post('/payment/link', [PaymentLinkController::class, 'generateLink']);
Route::post('/payment/verify', [PaymentLinkController::class, 'verifyPayment'])->name('payment.verify');
