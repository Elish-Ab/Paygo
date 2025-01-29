<?php

namespace App\Http\Controllers;

use telegram;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WalletController extends Controller
{
    public function createWallet(Request $request)
    {
        $telegram->onCommand('create_wallet', function ($update) use ($telegram) {
            $chatId = $update->getMessage()->getChat()->getId();
            $telegramId = $update->getMessage()->getFrom()->getId();
            $name = $update->getMessage()->getFrom()->getFirstName();

            // Check if the user already exists
            $user = User::where('telegram_id', $telegramId)->first();

            if (!$user) {
                // Register the user if not already registered
                $user = User::create([
                    'telegram_id' => $telegramId,
                    'name' => $name,
                    'email' => null, // Optional: Collect email later
                ]);
            }

            // Check if the wallet already exists
            if ($user->wallet) {
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "You already have a wallet. Your current balance is: {$user->wallet->balance} {$user->wallet->currency}",
                ]);
                return;
            }

            // Create a new wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0, // Initialize with zero balance
                'currency' => 'ETB', // Default currency
            ]);

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Your wallet has been created successfully! Your current balance is: 0 ETB.",
            ]);
        });
    }




























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
