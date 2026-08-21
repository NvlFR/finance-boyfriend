<?php

use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Models\Wallet;

test('authenticated user can view savings goals', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('goals.index'));

    $response->assertOk();
});

test('user can create a new savings goal', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)->post(route('goals.store'), [
        'name' => 'Liburan ke Bali',
        'target_amount' => 10000000,
        'target_date' => '2027-12-31',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('savings_goals', [
        'name' => 'Liburan ke Bali',
        'couple_space_id' => $space->id,
    ]);
});

test('user can contribute to a savings goal and deduct wallet', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 2000000,
    ]);

    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'target_amount' => 5000000,
        'current_amount' => 0,
    ]);

    $response = $this->actingAs($user)->post(route('goals.contribute', $goal), [
        'amount' => 500000,
        'wallet_id' => $wallet->id,
        'notes' => 'Tabungan awal',
    ]);

    $response->assertRedirect();

    expect((float) $goal->fresh()->current_amount)->toBe(500000.0)
        ->and((float) $wallet->fresh()->balance)->toBe(1500000.0);

    $this->assertDatabaseHas('savings_contributions', [
        'savings_goal_id' => $goal->id,
        'user_id' => $user->id,
        'amount' => 500000,
    ]);
});
