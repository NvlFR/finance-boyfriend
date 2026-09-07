<?php

namespace Database\Factories;

use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentTransaction>
 */
class InvestmentTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(8, 1, 10);
        $unitPrice = fake()->randomFloat(2, 10000, 100000);

        return [
            'investment_id' => Investment::factory(),
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'type' => 'buy',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'gross_amount' => round($quantity * $unitPrice, 2),
            'fee_amount' => 0,
            'realized_profit_loss' => 0,
            'transaction_date' => now(),
            'client_reference' => fake()->uuid(),
            'notes' => null,
        ];
    }
}
