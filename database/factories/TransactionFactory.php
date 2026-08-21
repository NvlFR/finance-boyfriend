<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_space_id' => CoupleSpace::factory(),
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'to_wallet_id' => null,
            'category_id' => Category::factory(),
            'type' => 'expense',
            'scope' => 'personal',
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'transaction_date' => now(),
            'title' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'receipt_image_path' => null,
        ];
    }
}
