<?php

use App\Models\CoupleSpace;
use App\Models\Trip;
use App\Models\User;

test('authenticated user can view live trip page', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::create([
        'name' => 'Test Space',
        'invite_code' => 'TESTCODE',
        'user_one_id' => $user->id,
        'status' => 'pending',
    ]);
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)->get('/trips');

    $response->assertStatus(200);
});

test('user can start a new live trip', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::create([
        'name' => 'Test Space',
        'invite_code' => 'TESTCODE',
        'user_one_id' => $user->id,
        'status' => 'pending',
    ]);
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)->post('/trips', [
        'title' => 'OTW Tempat Kencan',
        'destination_name' => 'Mall Grand Indonesia',
        'origin_lat' => -6.175392,
        'origin_lng' => 106.827153,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('trips', [
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'title' => 'OTW Tempat Kencan',
        'status' => 'active',
    ]);
});

test('user can update current live position and speed', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::create([
        'name' => 'Test Space',
        'invite_code' => 'TESTCODE',
        'user_one_id' => $user->id,
        'status' => 'pending',
    ]);
    $user->update(['current_couple_space_id' => $space->id]);

    $trip = Trip::create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'title' => 'OTW Kencan',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->post("/trips/{$trip->id}/position", [
        'lat' => -6.190000,
        'lng' => 106.830000,
        'speed' => 45.5,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'current_lat' => -6.190000,
        'current_lng' => 106.830000,
        'speed' => 45.5,
    ]);
});

test('user can complete a live trip', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::create([
        'name' => 'Test Space',
        'invite_code' => 'TESTCODE',
        'user_one_id' => $user->id,
        'status' => 'pending',
    ]);
    $user->update(['current_couple_space_id' => $space->id]);

    $trip = Trip::create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'title' => 'OTW Kencan',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->post("/trips/{$trip->id}/complete");

    $response->assertRedirect();
    $this->assertDatabaseHas('trips', [
        'id' => $trip->id,
        'status' => 'completed',
    ]);
});

test('server calculates trip distance and ignores client supplied distance', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $trip = Trip::create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'title' => 'Perjalanan Aman',
        'status' => 'active',
        'current_lat' => -6.2,
        'current_lng' => 106.8,
    ]);

    $this->actingAs($user)->postJson(route('trips.position', $trip), [
        'lat' => -6.201,
        'lng' => 106.8,
        'speed' => 20,
        'distance_km' => 99999,
    ])->assertOk();

    expect($trip->fresh()->total_distance_km)->toBeGreaterThan(0.05)
        ->and($trip->fresh()->total_distance_km)->toBeLessThan(1.0);
});

test('user cannot start a trip while partner has an active trip', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    Trip::create(['couple_space_id' => $space->id, 'user_id' => $partner->id, 'title' => 'Partner OTW', 'status' => 'active']);

    $this->actingAs($user)->postJson(route('trips.store'), [
        'title' => 'Trip Bentrok',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('title');
});

test('completed trip cannot receive new positions', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);
    $trip = Trip::create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'title' => 'Selesai', 'status' => 'completed']);

    $this->actingAs($user)->postJson(route('trips.position', $trip), [
        'lat' => -6.2,
        'lng' => 106.8,
    ])->assertForbidden();
});

test('push subscription stores encryption keys and endpoint ownership', function () {
    $user = User::factory()->create();
    $endpoint = 'https://push.example.test/subscription/123';

    $this->actingAs($user)->postJson(route('push.subscribe'), [
        'endpoint' => $endpoint,
        'public_key' => 'public-key',
        'auth_token' => 'auth-token',
        'content_encoding' => 'aes128gcm',
    ])->assertOk();

    $this->assertDatabaseHas('push_subscriptions', [
        'user_id' => $user->id,
        'endpoint_hash' => hash('sha256', $endpoint),
        'public_key' => 'public-key',
        'auth_token' => 'auth-token',
    ]);
});
