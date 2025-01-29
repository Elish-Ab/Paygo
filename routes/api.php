<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChapaController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\EnsureUserIsOwner;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TransactionController;


Route::post('/wallet-to-wallet', [TransactionController::class, 'walletToWallet']);


Route::post('/chapa/initialize', [ChapaController::class, 'initialize']);
Route::get('/chapa/callback', [ChapaController::class, 'callback'])->name('chapa.callback');

Route::get('/telegram/set-webhook', [TelegramController::class, 'setWebhook']);
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);








// Auth routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/signup', [UserController::class, 'create_user']);
// Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

// PaymentController routes
Route::post('/initalize-payment', [PaymentController::class, 'initalizePaymentController'])->name('initalize');
Route::post('/generate-link', [PaymentController::class, 'generateLink'])->name('generate');
Route::post('/webhook', [PaymentController::class, 'handleWebhook'])->name('handleWebhook');
Route::middleware('auth:sanctum')->post('/initalize-payment', [PaymentController::class, 'initalize-payment']);

// // Authenticated routes
// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::get('/user', function (Request $request) {
//         $user = $request->user();
//         return response()->json(['message' => 'User authenticated successfully', 'user' => $user], 200);
//     });

//     Route::get('user/{id}', [WalletController::class, 'check_balance'])->middleware(EnsureUserIsOwner::class);

//     // Corrected route for transfer
//     Route::post('/transaction/transfer', [TransactionController::class, 'transfer']); // No need for EnsureUserIsOwner middleware here

//     // Chapa callback route
//     Route::post('/callback', [TransactionController::class, 'chapaCallback']);
// });

// // Other PaymentController routes
// Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
// Route::get('/payment/return', [PaymentController::class, 'return'])->name('payment.return');
// Route::post('/payment/link', [PaymentController::class, 'generateLink']);
// Route::post('/payment/verify', [PaymentController::class, 'verify-payment'])->name('payment.verify');
