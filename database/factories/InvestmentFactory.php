<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Investment>
 */
class InvestmentFactory extends Factory
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
            'name' => fake()->randomElement(['Emas Digital', 'BBCA', 'Bitcoin', 'Reksa Dana Pasar Uang']),
            'symbol' => fake()->randomElement(['XAU', 'BBCA', 'BTC', 'RDPU']),
            'asset_type' => fake()->randomElement(['gold', 'stock', 'crypto', 'mutual_fund']),
            'scope' => 'personal',
            'quantity' => fake()->randomFloat(8, 1, 20),
            'average_buy_price' => fake()->randomFloat(2, 10000, 1000000),
            'current_price' => fake()->randomFloat(2, 10000, 1000000),
            'realized_profit_loss' => 0,
            'currency' => 'IDR',
            'is_active' => true,
        ];
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => 'shared',
            'user_id' => null,
        ]);
    }
}
