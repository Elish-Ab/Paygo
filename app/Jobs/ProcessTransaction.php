<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $validatedData = $this->data;

        DB::transaction(function () use ($validatedData) {
            // Retrieve sender and recipient
            $sender = User::lockForUpdate()->findOrFail($validatedData['id']);
            $recipient = User::lockForUpdate()->findOrFail($validatedData['recipient_id']);

            // Deduct amount from sender
            $sender->balance -= $validatedData['amount'];
            $sender->save();

            // Add amount to recipient
            $recipient->balance += $validatedData['amount'];
            $recipient->save();

            // Log the transaction
            Transaction::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'amount' => $validatedData['amount'],
                'status' => $validatedData['status'],
                'transaction_type' => $validatedData['transaction_type'],
                'reference' => $validatedData['reference'],
                'description' => $validatedData['description'] ?? null,
            ]);
        });
    }
}
