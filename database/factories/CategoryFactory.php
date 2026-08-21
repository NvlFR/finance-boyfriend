<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CoupleSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_space_id' => null,
            'name' => fake()->word(),
            'type' => fake()->randomElement(['income', 'expense']),
            'icon' => 'tag',
            'color' => fake()->safeHexColor(),
            'is_default' => true,
        ];
    }

    public function custom(int|CoupleSpace $coupleSpace): static
    {
        return $this->state(fn (array $attributes) => [
            'couple_space_id' => $coupleSpace instanceof CoupleSpace ? $coupleSpace->id : $coupleSpace,
            'is_default' => false,
        ]);
    }
}
