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
}
