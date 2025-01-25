<?php

namespace App\Http\Controllers;

use console;
use Illuminate\Http\Request;
use Chapa\Chapa\Facades\Chapa;
use App\Jobs\ProcessTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
            'transaction_type' => 'required|string|in:deposit,withdrawal,transfer',
            'reference' => 'nullable|string|unique:transactions,reference',
            'description' => 'nullable|string',
        ])->validate();

        try {
            // Generate a unique reference
            $validatedData['reference'] = Chapa::generateReference();

            // Dispatch the transaction job to RabbitMQ
            ProcessTransaction::dispatch($validatedData);
            Log::alert("Transaction sent to RabbitMQ", ['data' => $validatedData]);

            // Initialize payment with Chapa
            $payment = Chapa::initializePayment([
                'amount' => $validatedData['amount'],
                'email' => 'hi@negade.com',
                'tx_ref' => $validatedData['reference'],
                'currency' => 'ETB',
                'callback_url' => route('callback', ['reference' => $validatedData['reference']]),
                'first_name' => 'Test',
                'last_name' => 'User',
                "customization" => [
                    "title" => 'Chapa Payment',
                    "description" => "Payment for services",
                ],
            ]);

            if ($payment['status'] !== 'success') {
                return response()->json(['message' => 'Failed to initialize payment'], 500);
            }

            return redirect($payment['data']['checkout_url']);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch transaction', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to process transaction'], 500);
        }
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
