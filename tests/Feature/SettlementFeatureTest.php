<?php

use App\Models\CoupleSpace;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use App\Models\Wallet;

test('index returns calculated unsettled balance and settlement history', function () {
    $space = CoupleSpace::factory()->active()->create();
    $userOne = $space->userOne;
    $userTwo = $space->userTwo;

    $userOne->update(['current_couple_space_id' => $space->id]);
    $userTwo->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $userOne->id,
    ]);

    // Create a transaction where userOne paid 200,000 and userTwo owes 100,000
    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $userOne->id,
        'wallet_id' => $wallet->id,
        'amount' => 200000,
        'scope' => 'shared',
    ]);

    TransactionSplit::factory()->create([
        'transaction_id' => $transaction->id,
        'paid_by_user_id' => $userOne->id,
        'user_one_amount' => 100000,
        'user_two_amount' => 100000,
        'split_type' => 'split_equal',
        'settled' => false,
    ]);

    // Create a past settlement
    Settlement::factory()->create([
        'couple_space_id' => $space->id,
        'from_user_id' => $userTwo->id,
        'to_user_id' => $userOne->id,
        'amount' => 50000,
    ]);

    $response = $this->actingAs($userOne)
        ->getJson(route('settlements.index'));

    $response->assertOk()
        ->assertJsonPath('unsettled.debtor_id', $userTwo->id)
        ->assertJsonPath('unsettled.creditor_id', $userOne->id)
        ->assertJsonPath('unsettled.amount_owed', 100000)
        ->assertJsonCount(1, 'history.data');
});

test('storing settlement records payment and marks transaction splits as settled', function () {
    $space = CoupleSpace::factory()->active()->create();
    $userOne = $space->userOne;
    $userTwo = $space->userTwo;

    $userOne->update(['current_couple_space_id' => $space->id]);
    $userTwo->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $userOne->id,
    ]);

    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $userOne->id,
        'wallet_id' => $wallet->id,
        'amount' => 150000,
        'scope' => 'shared',
    ]);

    $split = TransactionSplit::factory()->create([
        'transaction_id' => $transaction->id,
        'paid_by_user_id' => $userOne->id,
        'user_one_amount' => 75000,
        'user_two_amount' => 75000,
        'settled' => false,
    ]);

    $response = $this->actingAs($userTwo)
        ->postJson(route('settlements.store'), [
            'to_user_id' => $userOne->id,
            'amount' => 75000,
            'payment_method' => 'Transfer BCA',
            'notes' => 'Settled dinner cost',
        ]);

    $response->assertCreated()
        ->assertJsonPath('settlement.amount', '75000.00');

    $this->assertDatabaseHas('settlements', [
        'couple_space_id' => $space->id,
        'from_user_id' => $userTwo->id,
        'to_user_id' => $userOne->id,
        'amount' => 75000,
    ]);

    expect($split->fresh()->settled)->toBeTrue();
});
