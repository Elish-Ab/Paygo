<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function generate_link(Request $request){

        $validateData = $request->validate([
            'user_id'=>'required|unique',
            'amount'=>'required|decimal',
            'reference'=>'required|unique',
            'description'=>'nullable|string',
            'is_paid'=>'required|boolean'
        ]);

        $user = USER::findOrFail($validateData->user_id);

        if($user){
            $link = PaymentLink::save($validated);
            return  response()->json(["message"=>"created link successfully"], 200);
        }
        else{
            return response()->json(["message"=>"Link not created"], 500);
        }

    }
}
