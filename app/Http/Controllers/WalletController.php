<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WalletController extends Controller
{
    public function check_balance(Request $request){

        $balance = Auth::user()->balance; // Only accessible if middleware passes
        return response()->json(['balance' => $balance], 200);
    }

    public function withdraw(Request $request){
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        $amount = $validatedData['amount'];

        if ($user->balance < $amount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        DB::transaction(function () use ($user, $amount) {
            $user->balance -= $amount;
            $user->save();

            // Log the transaction
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $amount,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Withdrawal successful'], 200);
}



    public function loadFunds(Request $request){

        $validatedData = $request->validate([
        'amount' => 'required|numeric|min:1',
    ]);

    // Prepare Chapa API request
        $chapaPayload = [
            'amount' => $validatedData['amount'],
            'email' => Auth::user()->email,
            'currency' => 'ETB',
            'callback_url' => route('chapa.callback'),
        ];

        // Make API request (example with Laravel HTTP Client)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY'),
        ])->post('https://api.chapa.co/v1/transaction/initialize', $chapaPayload);

        if ($response->successful()) {
            // Redirect user to Chapa payment page
            return response()->json(['payment_url' => $response->json('data.checkout_url')]);
        }

        return response()->json(['message' => 'Failed to initialize payment'], 500);
}

}
