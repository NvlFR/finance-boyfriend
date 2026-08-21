<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\TransactionSplit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionSplit>
 */
class TransactionSplitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'paid_by_user_id' => User::factory(),
            'user_one_amount' => 50000,
            'user_two_amount' => 50000,
            'split_type' => 'split_equal',
            'settled' => false,
        ];
    }
}
