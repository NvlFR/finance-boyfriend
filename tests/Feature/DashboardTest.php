<?php

use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard provides a shortcut to the trip tracker', function () {
    $dashboard = file_get_contents(resource_path('js/pages/Dashboard.vue'));

    expect($dashboard)
        ->toContain(':href="tripsIndex()"')
        ->toContain('Perjalanan')
        ->toContain('Bagikan lokasi dan pantau perjalanan pasangan');
});

test('dashboard net worth includes money moved into savings goals', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 700000]);
    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'current_amount' => 300000,
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('totalNetWorth', 1000000)
        ->where('jointNetWorth', 300000));
});

test('dashboard reports todays spending for each partner', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 75000,
        'transaction_date' => now(),
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 125000,
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('dailySpending', 200000)
        ->where('dailySpendingByUser.user', 75000)
        ->where('dailySpendingByUser.partner', 125000));
});
