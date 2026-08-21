<?php

namespace Database\Factories;

use App\Models\CoupleSpace;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'couple_space_id' => CoupleSpace::factory(),
            'paid_by_user_id' => User::factory(),
            'wallet_id' => null,
            'name' => fake()->word().' Subscription',
            'amount' => 186000,
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addMonth()->toDateString(),
            'split_mode' => '50_50',
            'icon' => 'repeat',
            'color' => '#6366F1',
            'is_active' => true,
        ];
    }
}
