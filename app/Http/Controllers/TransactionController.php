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
        $table->foreignIdFor(App\Models\User::class)->constrained('users');
        $table->foreignId('recipient_id')->nullable()->constrained('users');
        $table->decimal('amount', 15, 2);
        $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
        $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer', 'payment']);
        $table->string('reference')->unique();
        $table->string('description')->nullable();

        $sender = User::findOrFail($validateData->id);
        $transaction_amount  = $validateData->amount;
        $balance = $sender->amount;
        $recipient = User::findOrFail($validateData->recipient_id);

        if($balance>$transaction_amount){
            $amount = $balance - $transaction_amount;
            $sender->amount = $amount;
            User::update($sender);
            return response()->json(['message'=>'Money sent'],200);
        }


    }

}
