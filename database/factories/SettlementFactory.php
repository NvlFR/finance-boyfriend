<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
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
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'payment_method' => 'BCA Transfer',
            'notes' => fake()->optional()->sentence(),
            'settled_at' => now(),
        ];
    }
}
