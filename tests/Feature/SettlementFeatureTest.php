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
        ->assertJsonPath('unsettledItems.0.amount', '200000.00')
        ->assertJsonPath('unsettledItems.0.paid_by_name', $userOne->nickname ?: $userOne->name)
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

test('settlement rejects partial amount and leaves splits unsettled', function () {
    $space = CoupleSpace::factory()->active()->create();
    $creditor = $space->userOne;
    $debtor = $space->userTwo;
    $debtor->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $creditor->id]);
    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $creditor->id,
        'wallet_id' => $wallet->id,
        'scope' => 'shared',
        'amount' => 200000,
    ]);
    $split = TransactionSplit::factory()->create([
        'transaction_id' => $transaction->id,
        'paid_by_user_id' => $creditor->id,
        'user_one_amount' => 100000,
        'user_two_amount' => 100000,
        'settled' => false,
    ]);

    $this->actingAs($debtor)->postJson(route('settlements.store'), [
        'to_user_id' => $creditor->id,
        'amount' => 50000,
        'payment_method' => 'Transfer',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('amount');

    expect($split->fresh()->settled)->toBeFalse();
    $this->assertDatabaseCount('settlements', 0);
});

test('creditor cannot settle debt in the reverse direction', function () {
    $space = CoupleSpace::factory()->active()->create();
    $creditor = $space->userOne;
    $debtor = $space->userTwo;
    $creditor->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $creditor->id]);
    $transaction = Transaction::factory()->create(['couple_space_id' => $space->id, 'user_id' => $creditor->id, 'wallet_id' => $wallet->id, 'scope' => 'shared']);
    TransactionSplit::factory()->create([
        'transaction_id' => $transaction->id,
        'paid_by_user_id' => $creditor->id,
        'user_one_amount' => 50000,
        'user_two_amount' => 50000,
        'settled' => false,
    ]);

    $this->actingAs($creditor)->postJson(route('settlements.store'), [
        'to_user_id' => $debtor->id,
        'amount' => 50000,
        'payment_method' => 'Tunai',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('amount');
});

test('wallet settlement transfers balance and is idempotent', function () {
    $space = CoupleSpace::factory()->active()->create();
    $creditor = $space->userOne;
    $debtor = $space->userTwo;
    $debtor->update(['current_couple_space_id' => $space->id]);
    $creditorWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $creditor->id,
        'balance' => 100000,
    ]);
    $debtorWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $debtor->id,
        'balance' => 300000,
    ]);
    $expense = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $creditor->id,
        'wallet_id' => $creditorWallet->id,
        'scope' => 'shared',
        'amount' => 200000,
    ]);
    TransactionSplit::factory()->create([
        'transaction_id' => $expense->id,
        'paid_by_user_id' => $creditor->id,
        'user_one_amount' => 100000,
        'user_two_amount' => 100000,
        'settled' => false,
    ]);
    $payload = [
        'to_user_id' => $creditor->id,
        'amount' => 100000,
        'payment_method' => 'Transfer Dompet',
        'payment_mode' => 'wallet_transfer',
        'source_wallet_id' => $debtorWallet->id,
        'destination_wallet_id' => $creditorWallet->id,
        'client_reference' => 'settlement-request-1',
    ];

    $this->actingAs($debtor)->postJson(route('settlements.store'), $payload)->assertCreated();
    $this->actingAs($debtor)->postJson(route('settlements.store'), $payload)->assertCreated();

    expect($debtorWallet->fresh()->balance)->toBe('200000.00')
        ->and($creditorWallet->fresh()->balance)->toBe('200000.00');
    $this->assertDatabaseCount('settlements', 1);
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $debtorWallet->id,
        'to_wallet_id' => $creditorWallet->id,
        'type' => 'transfer',
        'amount' => 100000,
    ]);
});
