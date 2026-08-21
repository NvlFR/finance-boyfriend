<?php

use App\Models\CoupleSpace;
use App\Models\User;

test('user can view and create subscriptions', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->get(route('subscriptions.index'))->assertOk();

    $response = $this->actingAs($user)->post(route('subscriptions.store'), [
        'name' => 'Netflix 4K',
        'amount' => 186000,
        'billing_cycle' => 'monthly',
        'next_billing_date' => '2026-09-01',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subscriptions', [
        'name' => 'Netflix 4K',
        'couple_space_id' => $space->id,
    ]);
});
