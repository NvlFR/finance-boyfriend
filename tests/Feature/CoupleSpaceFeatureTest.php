<?php

use App\Models\CoupleSpace;
use App\Models\User;
use App\Models\Wallet;

test('authenticated user can view couple space index json', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)
        ->getJson(route('couple-space.index'));

    $response->assertOk()
        ->assertJsonPath('couple_space.id', $space->id);
});

test('user can create a new couple space with auto-generated invite code', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('couple-space.store'), [
            'name' => 'John & Jane Space',
            'anniversary_date' => '2025-06-15',
        ]);

    $response->assertCreated()
        ->assertJsonPath('couple_space.name', 'John & Jane Space')
        ->assertJsonStructure(['couple_space' => ['invite_code', 'user_one_id']]);

    $this->assertDatabaseHas('couple_spaces', [
        'name' => 'John & Jane Space',
        'user_one_id' => $user->id,
        'status' => 'pending',
    ]);

    expect($user->fresh()->current_couple_space_id)->not->toBeNull();
});

test('partner can join an existing couple space via invite code', function () {
    $creator = User::factory()->create();
    $space = CoupleSpace::factory()->create([
        'user_one_id' => $creator->id,
        'user_two_id' => null,
        'status' => 'pending',
    ]);

    $partner = User::factory()->create();

    $response = $this->actingAs($partner)
        ->postJson(route('couple-space.join'), [
            'invite_code' => $space->invite_code,
        ]);

    $response->assertOk()
        ->assertJsonPath('couple_space.status', 'active')
        ->assertJsonPath('couple_space.user_two_id', $partner->id);

    $this->assertDatabaseHas('couple_spaces', [
        'id' => $space->id,
        'user_two_id' => $partner->id,
        'status' => 'active',
    ]);

    expect($partner->fresh()->current_couple_space_id)->toBe($space->id);
});

test('creator cannot join their own couple space', function () {
    $creator = User::factory()->create();
    $space = CoupleSpace::factory()->create([
        'user_one_id' => $creator->id,
        'user_two_id' => null,
    ]);

    $response = $this->actingAs($creator)
        ->postJson(route('couple-space.join'), [
            'invite_code' => $space->invite_code,
        ]);

    $response->assertStatus(422);
});

test('third user cannot join an already full couple space', function () {
    $space = CoupleSpace::factory()->active()->create();
    $thirdUser = User::factory()->create();

    $response = $this->actingAs($thirdUser)
        ->postJson(route('couple-space.join'), [
            'invite_code' => $space->invite_code,
        ]);

    $response->assertStatus(422);
});

test('member can update couple space settings', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;

    $response = $this->actingAs($user)
        ->putJson(route('couple-space.update', $space), [
            'name' => 'Updated Space Name',
            'anniversary_date' => '2026-01-01',
        ]);

    $response->assertOk()
        ->assertJsonPath('couple_space.name', 'Updated Space Name');

    $this->assertDatabaseHas('couple_spaces', [
        'id' => $space->id,
        'name' => 'Updated Space Name',
    ]);
});

test('non-member cannot update couple space', function () {
    $space = CoupleSpace::factory()->active()->create();
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->putJson(route('couple-space.update', $space), [
            'name' => 'Hacked Space Name',
        ]);

    $response->assertForbidden();
});

test('user cannot create another couple space while one is active', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->postJson(route('couple-space.store'), [
        'name' => 'Ruang Duplikat',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('name');

    expect(CoupleSpace::where('user_one_id', $user->id)->count())->toBe(1);
});

test('user in an active space cannot join and move data into another space', function () {
    $oldSpace = CoupleSpace::factory()->active()->create();
    $user = $oldSpace->userOne;
    $user->update(['current_couple_space_id' => $oldSpace->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $oldSpace->id, 'user_id' => $user->id]);
    $targetSpace = CoupleSpace::factory()->create();

    $this->actingAs($user)->postJson(route('couple-space.join'), [
        'invite_code' => $targetSpace->invite_code,
    ])->assertUnprocessable()->assertJsonValidationErrorFor('invite_code');

    expect($wallet->fresh()->couple_space_id)->toBe($oldSpace->id)
        ->and($user->fresh()->current_couple_space_id)->toBe($oldSpace->id)
        ->and($targetSpace->fresh()->user_two_id)->toBeNull();
});

test('joining safely merges data from a personal pending space', function () {
    $user = User::factory()->create();
    $personalSpace = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $personalSpace->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $personalSpace->id, 'user_id' => $user->id]);
    $targetSpace = CoupleSpace::factory()->create();

    $this->actingAs($user)->postJson(route('couple-space.join'), [
        'invite_code' => $targetSpace->invite_code,
    ])->assertOk();

    expect($wallet->fresh()->couple_space_id)->toBe($targetSpace->id)
        ->and($user->fresh()->current_couple_space_id)->toBe($targetSpace->id);
    $this->assertDatabaseMissing('couple_spaces', ['id' => $personalSpace->id]);
});
