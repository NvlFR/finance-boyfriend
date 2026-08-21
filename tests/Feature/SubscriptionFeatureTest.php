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

test('user can update a subscription', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $sub = $space->subscriptions()->create([
        'created_by_user_id' => $user->id,
        'name' => 'Spotify Premium',
        'amount' => 55000,
        'billing_cycle' => 'monthly',
        'next_billing_date' => '2026-09-01',
    ]);

    $response = $this->actingAs($user)->put(route('subscriptions.update', $sub), [
        'name' => 'Spotify Duo',
        'amount' => 85000,
        'billing_cycle' => 'monthly',
        'next_billing_date' => '2026-09-01',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subscriptions', [
        'id' => $sub->id,
        'name' => 'Spotify Duo',
        'amount' => 85000,
    ]);
});

test('user can delete a subscription', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $sub = $space->subscriptions()->create([
        'created_by_user_id' => $user->id,
        'name' => 'Old Subscription',
        'amount' => 30000,
        'billing_cycle' => 'monthly',
        'next_billing_date' => '2026-09-01',
    ]);

    $response = $this->actingAs($user)->delete(route('subscriptions.destroy', $sub));
    $response->assertRedirect();
    $this->assertDatabaseMissing('subscriptions', ['id' => $sub->id]);
});
