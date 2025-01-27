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
        Schema::create('payment', function (Blueprint $table) {
            $table->uuid();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_gateway',['chapaa','mpesa','card']);
            $table->string('reference')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
