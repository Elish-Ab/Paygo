<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Add the user_id column
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade'); // Add recipient_id column
            $table->string('tx_ref')->unique();            // Unique transaction reference
            $table->string('reference')->unique();         // Add reference column
            $table->decimal('amount', 15, 2);             // Payment amount
            $table->string('currency');                   // Payment currency
            $table->string('email');                      // Payer's email
            $table->string('phone');                      // Payer's phone
            $table->string('callback_url');               // Callback URL for payment notifications
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending'); // Payment status
            $table->enum('transaction_type', ['payment', 'transfer'])->default('payment'); // Transaction type
            $table->text('description')->nullable();      // Additional notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
