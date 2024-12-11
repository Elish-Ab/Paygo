<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
            'transaction_type' => 'required|string|in:deposit,withdrawal,transfer',
            'reference' => 'required|string|unique:transactions,reference',
            'description' => 'nullable|string',
        ])->validate();

        // Dispatch the transaction job to RabbitMQ
        ProcessTransaction::dispatch($validatedData);

        return response()->json(['message' => 'Transaction is being processed'], 202);
    }

    public function chapaCallback(Request $request)
    {
        $validatedData = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        // Call Chapa API to verify transaction status
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY'),
        ])->get('https://api.chapa.co/v1/transaction/verify/' . $validatedData['transaction_id']);

        if ($response->successful() && optional($response->json())['status'] === 'success') {
            // Update user's wallet
            DB::transaction(function () use ($response) {
                $user = Auth::user();
                $amount = $response->json('data.amount');

                if (!$user) {
                    return response()->json(['message' => 'User not authenticated'], 401);
                }

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

        // Log the error in case of failure
        Log::error('Chapa verification failed', ['transaction_id' => $validatedData['transaction_id'], 'response' => $response->json()]);

        return response()->json(['message' => 'Transaction verification failed'], 400);
    }
}
