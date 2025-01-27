<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;

class TransactionService
{
    /**
     * Process a transaction between wallets.
     */
    public static function processTransaction(Wallet $fromWallet, Wallet $toWallet, float $amount)
    {
        // Debit sender's wallet
        $fromWallet->balance -= $amount;
        $fromWallet->save();

        // Credit recipient's wallet
        $toWallet->balance += $amount;
        $toWallet->save();

        // Create and return transaction record
        return Transaction::create([
            'from_wallet_id' => $fromWallet->id,
            'to_wallet_id' => $toWallet->id,
            'amount' => $amount,
            'status' => 'successful',
        ]);
    }

    /**
     * Add funds to a user's wallet.
     */
    public static function addFundsToWallet(Wallet $wallet, float $amount)
    {
        $wallet->balance += $amount;
        $wallet->save();
    }
}
