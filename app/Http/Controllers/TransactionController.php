<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessTransaction;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function transfer(Request $request)
    {
        // Validate the incoming request
        $validatedData = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'recipient_id' => 'required|integer|exists:users,id',
            'status' => 'required|string|in:pending,completed,canceled',
            'transaction_type' => 'required|string',
            'reference' => 'required|string|unique:transactions,reference',
            'description' => 'nullable|string',
        ])->validate();

        // Dispatch the transaction job to RabbitMQ
        ProcessTransaction::dispatch($validatedData);

        return response()->json(['message' => 'Transaction is being processed'], 202);
    }

    public function chapaCallback(Request $request){

        $validatedData = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY'),
        ])->get('https://api.chapa.co/v1/transaction/verify/' . $validatedData['transaction_id']);

        if ($response->successful() && $response->json('status') === 'success') {
            // Update user's wallet
            DB::transaction(function () use ($response) {
                $user = Auth::user();
                $amount = $response->json('data.amount');

                $user->balance += $amount;
                $user->save();

                // Log the transaction
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'type' => 'load',
                    'amount' => $amount,
                    'status' => 'completed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return response()->json(['message' => 'Wallet updated successfully']);
        }

        return response()->json(['message' => 'Transaction verification failed'], 400);
}

}
