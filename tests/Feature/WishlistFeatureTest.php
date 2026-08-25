<?php

use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\User;
use App\Models\Wallet;
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

test('secret surprise target cannot reveal modify or delete the gift', function () {
    $space = CoupleSpace::factory()->active()->create();
    $creator = $space->userOne;
    $target = $space->userTwo;
    $target->update(['current_couple_space_id' => $space->id]);
    $wishlist = Wishlist::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $creator->id,
        'target_user_id' => $target->id,
        'title' => 'Hadiah Rahasia',
        'is_secret_surprise' => true,
        'is_bought' => false,
    ]);

    $this->actingAs($target)->getJson(route('wishlists.index'))
        ->assertOk()
        ->assertJsonPath('wishlists.0.title', '🎁 Secret Surprise Gift for You!')
        ->assertJsonPath('wishlists.0.can_manage', false);

    $this->actingAs($target)->patch(route('wishlists.toggle', $wishlist))->assertForbidden();
    $this->actingAs($target)->put(route('wishlists.update', $wishlist), [
        'title' => 'Bocor',
    ])->assertForbidden();
    $this->actingAs($target)->delete(route('wishlists.destroy', $wishlist))->assertForbidden();

    expect($wishlist->fresh()->title)->toBe('Hadiah Rahasia');
});

test('buying a wishlist item records expense before marking it bought', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 1000000,
    ]);
    $category = Category::factory()->create(['type' => 'expense']);
    $wishlist = Wishlist::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'estimated_price' => 250000,
        'is_bought' => false,
    ]);

    $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'scope' => 'personal',
        'amount' => 250000,
        'transaction_date' => now()->toIso8601String(),
        'client_reference' => 'wishlist-payment-1',
        'source_type' => 'wishlist',
        'source_id' => $wishlist->id,
    ])->assertCreated();

    expect($wallet->fresh()->balance)->toBe('750000.00')
        ->and($wishlist->fresh()->is_bought)->toBeTrue();
});
