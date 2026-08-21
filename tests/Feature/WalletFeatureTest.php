<?php

use App\Models\CoupleSpace;
use App\Models\Wallet;

test('user can list wallets grouped by his, her, and joint with net worth calculations', function () {
    $space = CoupleSpace::factory()->active()->create();
    $userOne = $space->userOne;
    $userTwo = $space->userTwo;

    $userOne->update(['current_couple_space_id' => $space->id]);
    $userTwo->update(['current_couple_space_id' => $space->id]);

    Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $userOne->id,
        'name' => 'BCA User One',
        'type' => 'personal',
        'balance' => 1000000,
    ]);

    Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $userTwo->id,
        'name' => 'Mandiri User Two',
        'type' => 'personal',
        'balance' => 2000000,
    ]);

    Wallet::factory()->joint()->create([
        'couple_space_id' => $space->id,
        'name' => 'Kas Kencan',
        'balance' => 500000,
    ]);

    $response = $this->actingAs($userOne)
        ->getJson(route('wallets.index'));

    $response->assertOk()
        ->assertJsonPath('total_net_worth', 3500000)
        ->assertJsonPath('user_net_worth', 1000000)
        ->assertJsonPath('partner_net_worth', 2000000)
        ->assertJsonPath('joint_net_worth', 500000)
        ->assertJsonCount(1, 'his_wallets')
        ->assertJsonCount(1, 'her_wallets')
        ->assertJsonCount(1, 'joint_wallets');
});

test('user can create a personal wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)
        ->postJson(route('wallets.store'), [
            'name' => 'GoPay',
            'type' => 'personal',
            'wallet_type' => 'ewallet',
            'balance' => 250000,
            'color' => '#10B981',
            'icon' => 'smartphone',
        ]);

    $response->assertCreated()
        ->assertJsonPath('wallet.name', 'GoPay')
        ->assertJsonPath('wallet.user_id', $user->id)
        ->assertJsonPath('wallet.type', 'personal');

    $this->assertDatabaseHas('wallets', [
        'name' => 'GoPay',
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 250000,
    ]);
});

test('user can create a joint wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)
        ->postJson(route('wallets.store'), [
            'name' => 'Tabungan Liburan',
            'type' => 'joint',
            'wallet_type' => 'bank',
            'balance' => 1500000,
        ]);

    $response->assertCreated()
        ->assertJsonPath('wallet.name', 'Tabungan Liburan')
        ->assertJsonPath('wallet.user_id', null)
        ->assertJsonPath('wallet.type', 'joint');

    $this->assertDatabaseHas('wallets', [
        'name' => 'Tabungan Liburan',
        'couple_space_id' => $space->id,
        'user_id' => null,
        'type' => 'joint',
    ]);
});

test('user can update wallet balance and details', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'name' => 'Old Wallet',
        'balance' => 100000,
    ]);

    $response = $this->actingAs($user)
        ->putJson(route('wallets.update', $wallet), [
            'name' => 'Renamed Wallet',
            'balance' => 500000,
        ]);

    $response->assertOk()
        ->assertJsonPath('wallet.name', 'Renamed Wallet')
        ->assertJsonPath('wallet.balance', '500000.00');

    $this->assertDatabaseHas('wallets', [
        'id' => $wallet->id,
        'name' => 'Renamed Wallet',
        'balance' => 500000,
    ]);
});

test('user can delete a wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson(route('wallets.destroy', $wallet));

    $response->assertOk();
    $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
});
