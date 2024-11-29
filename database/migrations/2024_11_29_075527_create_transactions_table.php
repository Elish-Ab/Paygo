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
            $table->foreignIdFor(App\Models\User::class, 'sender_id')->constrained();
            $table->foreignIdFor(App\Models\User::class, 'recipient_id')->constrained();
            $table->float('amount',precision:4);
            $table->enum('status',['successful','failed']);
            $table->enum('type',['send','request']);
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
