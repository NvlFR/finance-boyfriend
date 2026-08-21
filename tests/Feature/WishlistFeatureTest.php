<?php

use App\Models\CoupleSpace;
use App\Models\User;
use App\Models\Wishlist;

test('user can view and add wishlists', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->get(route('wishlists.index'))->assertOk();

    $response = $this->actingAs($user)->post(route('wishlists.store'), [
        'title' => 'Coffee Maker',
        'estimated_price' => 750000,
        'priority' => 'high',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('wishlists', [
        'title' => 'Coffee Maker',
        'couple_space_id' => $space->id,
    ]);
});

test('user can toggle bought status on wishlist', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $wishlist = Wishlist::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'is_bought' => false,
    ]);

    $response = $this->actingAs($user)->patch(route('wishlists.toggle', $wishlist));
    $response->assertRedirect();

    expect($wishlist->fresh()->is_bought)->toBeTrue();
});

test('user can update a wishlist item', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $wishlist = Wishlist::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'title' => 'Old Item',
        'estimated_price' => 200000,
    ]);

    $response = $this->actingAs($user)->put(route('wishlists.update', $wishlist), [
        'title' => 'Updated Item',
        'estimated_price' => 350000,
        'priority' => 'high',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('wishlists', [
        'id' => $wishlist->id,
        'title' => 'Updated Item',
        'estimated_price' => 350000,
    ]);
});

test('user can delete a wishlist item', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $wishlist = Wishlist::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('wishlists.destroy', $wishlist));
    $response->assertRedirect();
    $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
});
