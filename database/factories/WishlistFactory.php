<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
{
    protected $model = Wishlist::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_space_id' => CoupleSpace::factory(),
            'user_id' => User::factory(),
            'title' => fake()->words(2, true),
            'estimated_price' => 500000,
            'priority' => 'medium',
            'url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
            'is_secret_surprise' => false,
            'target_user_id' => null,
            'is_bought' => false,
        ];
    }
}
