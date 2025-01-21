<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\EnsureUserIsOwner;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\TransactionController;


// Auth routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/signup', [UserController::class, 'create_user']);

// Payment routes
Route::post('/initalize-payment', [PaymentLinkController::class, 'initalizePayment'])->name('initalize');
Route::post('/generate-link', [PaymentLinkController::class, 'generateLink'])->name('generate');
Route::post('/webhook', [PaymentLinkController::class, 'handleWebhook'])->name('handleWebhook');
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) { $user = $request->user(); return response()->json(['message' => 'User authenticated successfully', 'user' => $user], 200);}); });
    Route::get('user/{id}', [WalletController::class, 'check_balance'])->middleware(EnsureUserIsOwner::class);
    Route::post('/transfer', [TransactionController::class, 'transfer'])->middleware(EnsureUserIsOwner::class);

    // Route::post('/pay', 'App\Http\Controllers\ChapaController@initialize')->name('pay');



    // The callback url after a payment
    Route::get('callback/{reference}', 'App\Http\Controllers\ChapaController@callback')->name('callback');

    // The redirect url after a payment
// Route::post('/payment/initialize', [PaymentLinkController::class, 'initializePayment'])->name('payment.initialize');
Route::get('/payment/callback', [PaymentLinkController::class, 'callback'])->name('payment.callback');
Route::get('/payment/return', [PaymentLinkController::class, 'return'])->name('payment.return');
Route::post('/payment/link', [PaymentLinkController::class, 'generateLink']);
Route::post('/payment/verify', [PaymentLinkController::class, 'verifyPayment'])->name('payment.verify');
