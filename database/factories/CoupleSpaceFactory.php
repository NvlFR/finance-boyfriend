<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoupleSpace>
 */
class CoupleSpaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName().' & '.fake()->firstName().' Space',
            'invite_code' => CoupleSpace::generateInviteCode(),
            'user_one_id' => User::factory(),
            'user_two_id' => null,
            'status' => 'pending',
            'anniversary_date' => fake()->optional()->date(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_two_id' => User::factory(),
            'status' => 'active',
        ]);
    }
}
