<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function transfer(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'recipient_id' => 'required|integer|exists:users,id',
            'status' => 'required|string|in:pending,completed,canceled',
            'transaction_type' => 'required|string',
            'reference' => 'required|string|unique:transactions,reference',
            'description' => 'nullable|string',
        ]);

        // Retrieve sender and recipient
        $sender = User::findOrFail($validatedData['id']);
        $recipient = User::findOrFail($validatedData['recipient_id']);

        // Ensure sender has sufficient balance
        if ($sender->balance < $validatedData['amount']) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        // Perform the transaction within a database transaction
        DB::transaction(function () use ($sender, $recipient, $validatedData) {
            // Deduct amount from sender
            $sender->balance -= $validatedData['amount'];
            $sender->save();

            // Add amount to recipient
            $recipient->balance += $validatedData['amount'];
            $recipient->save();

            // Log the transaction (assuming a Transaction model exists)
            \App\Models\Transaction::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'amount' => $validatedData['amount'],
                'status' => $validatedData['status'],
                'transaction_type' => $validatedData['transaction_type'],
                'reference' => $validatedData['reference'],
                'description' => $validatedData['description'] ?? null,
            ]);
        });

        return response()->json(['message' => 'Money sent successfully'], 200);
    }
}
