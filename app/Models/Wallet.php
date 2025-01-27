<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function transaction(){
        return $this->hasMany(Transaction::class);
    }
}
