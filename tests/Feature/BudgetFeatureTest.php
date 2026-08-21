<?php

use App\Models\CoupleSpace;
use App\Models\User;

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
