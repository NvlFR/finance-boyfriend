<?php

use App\Models\CoupleSpace;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

test('user can view and create category budgets', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->get(route('budgets.index'))->assertOk();

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'name' => 'Makan Kencan Bulanan',
        'limit_amount' => 2500000,
        'scope' => 'shared',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('budgets', [
        'name' => 'Makan Kencan Bulanan',
        'couple_space_id' => $space->id,
    ]);
});

test('user can update a budget', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $budget = $space->budgets()->create([
        'created_by_user_id' => $user->id,
        'name' => 'Old Budget',
        'limit_amount' => 1000000,
        'scope' => 'shared',
    ]);

    $response = $this->actingAs($user)->put(route('budgets.update', $budget), [
        'name' => 'Revised Budget',
        'limit_amount' => 1500000,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('budgets', [
        'id' => $budget->id,
        'name' => 'Revised Budget',
        'limit_amount' => 1500000,
    ]);
});

test('user can delete a budget', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $budget = $space->budgets()->create([
        'created_by_user_id' => $user->id,
        'name' => 'Old Budget',
        'limit_amount' => 1000000,
        'scope' => 'shared',
    ]);

    $response = $this->actingAs($user)->delete(route('budgets.destroy', $budget));
    $response->assertRedirect();
    $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
});

test('budget percentage reports actual overage above one hundred percent', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);

    $space->budgets()->create([
        'created_by_user_id' => $user->id,
        'name' => 'Budget Terlampaui',
        'limit_amount' => 100000,
        'scope' => 'shared',
    ]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 150000,
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)
        ->getJson(route('budgets.index'))
        ->assertOk()
        ->assertJsonPath('budgets.0.percentage', 150)
        ->assertJsonPath('budgets.0.is_overbudget', true);
});
