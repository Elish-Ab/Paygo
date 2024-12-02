<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function generate_link(Request $request){
        $table->foreignId('user_id')->constrained('users');
        $table->decimal('amount', 15, 2);
        $table->string('reference')->unique();
        $table->string('description')->nullable();
        $table->boolean('is_paid')->default(false);
        $table->timestamp('expires_at')->nullable();
        $validateData = $request->validate([
            'user_id'=>'required|unique',
            'amount'=>'required|decimal',
            'reference'=>'required|unique',
            
        ]);
    }
}
