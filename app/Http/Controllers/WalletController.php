<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function check_balance(Request $request){

        $balance = Auth::user()->balance; // Only accessible if middleware passes
        return response()->json(['balance' => $balance], 200);
    }

    public function withdraw(Request $request){
        $validateData = $request->validate([
            'id'=> 'required|integer',
            'amount'=>"required|integer",

        ]);

        $user = User::findOrFail($validateData->id);
        $withdraw = $validateData->amount;
        $balance = $user->balance;
        if($balance>=50 & $balance > $withdraw){
                $withdraw = $balance - $withdraw;
                return response()->json(['message' => 'Withdraw successfully'], 201);
        }else{
            return response()->json(["message"=>"withdraw successfully"], 200);
        }
    }
}
