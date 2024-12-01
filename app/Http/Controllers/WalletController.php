<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function check_balance(Request $request){
        $user = User::findOrFail($request->id);
        $balance = $user->balance;
        return $balance;
    }
}
