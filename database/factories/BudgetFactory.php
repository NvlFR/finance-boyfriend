<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\CoupleSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_space_id' => CoupleSpace::factory(),
            'category_id' => null,
            'name' => fake()->word().' Budget',
            'limit_amount' => 2000000,
            'period' => 'monthly',
            'scope' => 'shared',
            'user_id' => null,
        ];
    }
}
