<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/home', function(Request $request){
    return "hi";
});
// Route::post('/tokens/create', function (Request $request) {
//     $token = $request->user()->createToken($request->token_name);

//     return ['token' => $token->plainTextToken];
// });

Route::post('/user', [UserController::class, 'create_user']);
Route::get('user/{id}', [WalletController::class, 'check_balance']);
Route::post('/transfer', [TransactionController::class, 'transfer']);
