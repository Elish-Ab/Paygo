<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AuthController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');





Route::post('/user', [UserController::class, 'create_user']);



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return "hi";
    });


    Route::get('user/{id}', [WalletController::class, 'check_balance']);
    Route::post('/transfer', [TransactionController::class, 'transfer']);
});
