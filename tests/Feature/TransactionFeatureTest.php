<?php

use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\Transaction;
use App\Models\Wallet;

test('user can list transactions with filters', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);

    $category = Category::factory()->create();

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'scope' => 'personal',
        'type' => 'expense',
    ]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'scope' => 'shared',
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('transactions.index', ['scope' => 'shared']));

    $response->assertOk()
        ->assertJsonCount(1, 'transactions.data');
});

test('user can store income and balance increments', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 100000,
    ]);

    $category = Category::factory()->create(['type' => 'income']);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'income',
            'scope' => 'personal',
            'amount' => 500000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Monthly Salary',
        ]);

    $response->assertCreated()
        ->assertJsonPath('transaction.amount', '500000.00');

    expect($wallet->fresh()->balance)->toBe('600000.00');
});

test('user can store expense and balance decrements', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 500000,
    ]);

    $category = Category::factory()->create(['type' => 'expense']);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'scope' => 'personal',
            'amount' => 150000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Dinner',
        ]);

    $response->assertCreated();
    expect($wallet->fresh()->balance)->toBe('350000.00');
});

test('user can store transfer between wallets', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $source = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 500000,
    ]);

    $dest = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 100000,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $source->id,
            'to_wallet_id' => $dest->id,
            'type' => 'transfer',
            'scope' => 'personal',
            'amount' => 200000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Transfer to savings',
        ]);

    $response->assertCreated();
    expect($source->fresh()->balance)->toBe('300000.00')
        ->and($dest->fresh()->balance)->toBe('300000.00');
});

test('storing shared expense creates transaction split record automatically', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 1000000,
    ]);

    $category = Category::factory()->create(['type' => 'expense']);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'scope' => 'shared',
            'amount' => 200000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Romantic Dinner',
            'split' => [
                'paid_by_user_id' => $user->id,
                'split_type' => 'split_equal',
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('transaction.split.user_one_amount', '100000.00')
        ->assertJsonPath('transaction.split.user_two_amount', '100000.00')
        ->assertJsonPath('transaction.split.settled', false);

    $this->assertDatabaseHas('transaction_splits', [
        'user_one_amount' => 100000,
        'user_two_amount' => 100000,
        'settled' => false,
    ]);
});

test('destroying transaction rolls back wallet balance', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 800000,
    ]);

    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 200000,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson(route('transactions.destroy', $transaction));

    $response->assertOk();
    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    expect($wallet->fresh()->balance)->toBe('1000000.00');
});
