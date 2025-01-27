<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    protected  $fillable= ['amount'];

    public function transaction(){
        return $this->belongsTo(Transaction::class);
    }


}
