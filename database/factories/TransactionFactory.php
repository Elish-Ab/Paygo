<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'amount' => $this->faker->randomNumber(4),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'transaction_type' => 'transfer',
            'user_id' => User::factory(),
            'reference' => $this->faker->uuid(),
            'description' => $this->faker->sentence(),
        ];
    }
}
