<?php

use App\Models\Category;
use App\Models\CoupleSpace;

test('user can list default categories and space custom categories', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    Category::factory()->create([
        'name' => 'Default Food',
        'type' => 'expense',
        'is_default' => true,
        'couple_space_id' => null,
    ]);

    Category::factory()->create([
        'name' => 'Our Custom Date Category',
        'type' => 'expense',
        'is_default' => false,
        'couple_space_id' => $space->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('categories.index'));

    $response->assertOk()
        ->assertJsonCount(2, 'categories')
        ->assertJsonCount(2, 'expense_categories');
});

test('user can create a custom category in their couple space', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)
        ->postJson(route('categories.store'), [
            'name' => 'Pet & Cat Supplies',
            'type' => 'expense',
            'icon' => 'cat',
            'color' => '#10B981',
        ]);

    $response->assertCreated()
        ->assertJsonPath('category.name', 'Pet & Cat Supplies')
        ->assertJsonPath('category.is_default', false)
        ->assertJsonPath('category.couple_space_id', $space->id);

    $this->assertDatabaseHas('categories', [
        'name' => 'Pet & Cat Supplies',
        'couple_space_id' => $space->id,
        'is_default' => false,
    ]);
});

test('user can update custom category', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $category = Category::factory()->create([
        'name' => 'Old Category',
        'type' => 'expense',
        'is_default' => false,
        'couple_space_id' => $space->id,
    ]);

    $response = $this->actingAs($user)
        ->putJson(route('categories.update', $category), [
            'name' => 'Renamed Category',
            'type' => 'expense',
            'color' => '#6366F1',
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Renamed Category',
    ]);
});

test('user cannot update default system category', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $defaultCategory = Category::factory()->create([
        'name' => 'Default Food',
        'type' => 'expense',
        'is_default' => true,
        'couple_space_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->putJson(route('categories.update', $defaultCategory), [
            'name' => 'Hacked Food',
            'type' => 'expense',
        ]);

    $response->assertForbidden();
    expect($defaultCategory->fresh()->name)->toBe('Default Food');
});

test('user can delete custom category', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $category = Category::factory()->create([
        'name' => 'Disposable Category',
        'type' => 'expense',
        'is_default' => false,
        'couple_space_id' => $space->id,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson(route('categories.destroy', $category));

    $response->assertOk();
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
