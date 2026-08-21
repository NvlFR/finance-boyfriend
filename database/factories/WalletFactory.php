<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
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
            'name' => fake()->randomElement(['BCA Utama', 'Mandiri', 'GoPay', 'ShopeePay', 'Dompet Tunai']),
            'type' => 'personal',
            'wallet_type' => fake()->randomElement(['bank', 'ewallet', 'cash', 'investment', 'credit_card']),
            'account_number' => fake()->optional()->bankAccountNumber(),
            'balance' => fake()->randomFloat(2, 50000, 5000000),
            'currency' => 'IDR',
            'color' => fake()->safeHexColor(),
            'icon' => 'wallet',
            'is_active' => true,
        ];
    }

    public function joint(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'joint',
            'user_id' => null,
            'name' => 'Kas Bersama',
        ]);
    }
}
