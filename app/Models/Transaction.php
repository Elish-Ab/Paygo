<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Faker\Provider\pt_PT\Payment;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'tx_ref',
        'amount',
        'currency',
        'email',
        'phone',
        'callback_url',
        'status',
        'transaction_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'status' => 'string',
        'transaction_type' => 'string',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function payment(){
        return $this->hasOne(Payment::class);
    }

}
