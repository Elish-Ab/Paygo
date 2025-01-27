<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Chapa\Chapa\Facades\Chapa;
use App\Jobs\ProcessTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Transfer funds from a bank account to a wallet.
     */
    public function bankToWallet(Request $request)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validatedData['user_id']);

        try {
            // Initialize payment via Chapa
            $payment = Chapa::initialize([
                "amount" => $validatedData['amount'],
                "email" => $user->email,
            ]);

            if ($payment->status === "success") {
                DB::transaction(function () use ($validatedData, $user) {
                    $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

                    TransactionService::addFundsToWallet($wallet, $validatedData['amount']);

                    Transaction::create([
                        'from_wallet_id' => null,
                        'to_wallet_id' => $wallet->id,
                        'amount' => $validatedData['amount'],
                        'status' => 'successful',
                    ]);
                });

                return response()->json(['message' => 'Funds added to wallet successfully.']);
            }

            return response()->json(['message' => 'Payment failed. Please try again later.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Transfer funds from one wallet to another.
     */
    public function walletToWallet(Request $request)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:1',
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id',
        ]);

        $fromWallet = Wallet::where('user_id', $validatedData['from_user_id'])->first();
        $toWallet = Wallet::where('user_id', $validatedData['to_user_id'])->first();

        if (!$fromWallet || !$toWallet) {
            return response()->json(['message' => 'Invalid wallets.'], 404);
        }

        if ($fromWallet->balance < $validatedData['amount']) {
            return response()->json(['message' => 'Insufficient funds.'], 400);
        }

        try {
            $transaction = TransactionService::processTransaction($fromWallet, $toWallet, $validatedData['amount']);
            return response()->json(['message' => 'Transaction successful.', 'transaction' => $transaction]);
        } catch (\Exception $e) {
            Log::error('Wallet-to-wallet transaction failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Transaction failed.'], 500);
        }
    }

    /**
     * Handle Chapa callback for payment verification.
     */
    public function chapaCallback(Request $request)
    {
        $validatedData = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY'),
        ])->get('https://api.chapa.co/v1/transaction/verify/' . $validatedData['transaction_id']);

        if ($response->successful() && optional($response->json())['status'] === 'success') {
            DB::transaction(function () use ($response) {
                $amount = $response->json('data.amount');
                $userId = $response->json('data.metadata.user_id'); // Ensure metadata is passed during initialization
                $wallet = Wallet::firstOrCreate(['user_id' => $userId]);

                TransactionService::addFundsToWallet($wallet, $amount);

                Transaction::create([
                    'from_wallet_id' => null,
                    'to_wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'status' => 'completed',
                ]);
            });

            return response()->json(['message' => 'Wallet updated successfully']);
        }

        Log::error('Chapa verification failed', ['transaction_id' => $validatedData['transaction_id'], 'response' => $response->json()]);

        return response()->json(['message' => 'Transaction verification failed'], 400);
    }
}
