<?php

use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

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

test('paying a subscription records one expense and advances its billing date', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 500000,
    ]);
    $category = Category::factory()->create(['type' => 'expense']);
    $subscription = $space->subscriptions()->create([
        'paid_by_user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'name' => 'Netflix',
        'amount' => 100000,
        'billing_cycle' => 'monthly',
        'next_billing_date' => '2026-09-01',
    ]);
    $payload = [
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'scope' => 'personal',
        'amount' => 100000,
        'transaction_date' => '2026-09-01 10:00:00',
        'title' => 'Bayar Netflix',
        'client_reference' => 'subscription-payment-1',
        'source_type' => 'subscription',
        'source_id' => $subscription->id,
    ];

    $this->actingAs($user)->postJson(route('transactions.store'), $payload)->assertCreated();
    $this->actingAs($user)->postJson(route('transactions.store'), $payload)->assertCreated();

    expect($wallet->fresh()->balance)->toBe('400000.00')
        ->and($subscription->fresh()->next_billing_date->toDateString())->toBe('2026-10-01')
        ->and($subscription->fresh()->last_paid_at)->not->toBeNull();
    $this->assertDatabaseCount('transactions', 1);

    $transaction = Transaction::query()->firstOrFail();
    $this->actingAs($user)
        ->deleteJson(route('transactions.destroy', $transaction))
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('transaction');
});
