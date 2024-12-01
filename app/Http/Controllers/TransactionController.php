<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function transfer(Request $request){
        $validateData =$request->validate([
            "id"=>"required|integer",
            "amount"=>"required|numeric",
            "recipient_id"=>"required|id",
            "status"=>"required|string",
            "transaction_type"=>"required|string",
            "reference"=>"required|string",
            "description"=>"nullable|string"
        ]);

        $sender = User::findOrFail($validateData->id);
        $amount = $sender->amount;
        $recipient = User::findOrFail($validateData->recipient_id);

        
    }

}
