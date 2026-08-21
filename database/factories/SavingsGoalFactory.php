<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
class SavingsGoalFactory extends Factory
{
    protected $model = SavingsGoal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_space_id' => CoupleSpace::factory(),
            'created_by_user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'target_amount' => 10000000,
            'current_amount' => 0,
            'target_date' => fake()->optional()->date(),
            'icon' => 'target',
            'color' => '#6366F1',
            'status' => 'in_progress',
        ];
    }
}
