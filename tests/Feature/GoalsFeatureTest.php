<?php

use App\Models\CoupleSpace;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can view savings goals', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('goals.index'));

    $response->assertOk();
});

test('user can create a new savings goal', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $response = $this->actingAs($user)->post(route('goals.store'), [
        'name' => 'Liburan ke Bali',
        'target_amount' => 10000000,
        'target_date' => '2027-12-31',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('savings_goals', [
        'name' => 'Liburan ke Bali',
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'scope' => 'personal',
    ]);
});

test('user can create a shared savings goal', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->post(route('goals.store'), [
        'name' => 'Dana Pernikahan',
        'target_amount' => 50000000,
        'scope' => 'shared',
    ])->assertRedirect();

    $this->assertDatabaseHas('savings_goals', [
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'name' => 'Dana Pernikahan',
        'scope' => 'shared',
    ]);
});

test('savings goal rejects an invalid ownership type', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->post(route('goals.store'), [
        'name' => 'Target Tidak Valid',
        'target_amount' => 1000000,
        'scope' => 'public',
    ])->assertSessionHasErrors('scope');

    expect(SavingsGoal::query()->where('name', 'Target Tidak Valid')->exists())->toBeFalse();
});

test('savings goals page exposes personal and shared ownership', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'scope' => 'personal',
    ]);
    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'scope' => 'shared',
    ]);

    $response = $this->actingAs($user)->get(route('goals.index'));
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Goals/Index')
        ->has('goals', 2));

    $goals = collect($response->inertiaProps('goals'));

    expect($goals->pluck('scope')->sort()->values()->all())->toBe(['personal', 'shared'])
        ->and($goals->pluck('created_by_user_id')->unique()->all())->toBe([$user->id]);
});

test('user can contribute to a savings goal and deduct wallet', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 2000000,
    ]);

    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'target_amount' => 5000000,
        'current_amount' => 0,
    ]);

    $response = $this->actingAs($user)->post(route('goals.contribute', $goal), [
        'amount' => 500000,
        'wallet_id' => $wallet->id,
        'notes' => 'Tabungan awal',
    ]);

    $response->assertRedirect();

    expect((float) $goal->fresh()->current_amount)->toBe(500000.0)
        ->and((float) $wallet->fresh()->balance)->toBe(1500000.0);

    $this->assertDatabaseHas('savings_contributions', [
        'savings_goal_id' => $goal->id,
        'user_id' => $user->id,
        'amount' => 500000,
    ]);
});

test('user can update a savings goal', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'name' => 'Old Goal',
        'target_amount' => 5000000,
    ]);

    $response = $this->actingAs($user)->put(route('goals.update', $goal), [
        'name' => 'Renamed Goal',
        'target_amount' => 8000000,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('savings_goals', [
        'id' => $goal->id,
        'name' => 'Renamed Goal',
        'target_amount' => 8000000,
    ]);
});

test('user can delete a savings goal', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);

    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('goals.destroy', $goal));

    $response->assertRedirect();
    $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
});

test('contribution rejects wallet from another couple space', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);
    $foreignWallet = Wallet::factory()->create(['balance' => 1000000]);
    $goal = SavingsGoal::factory()->create(['couple_space_id' => $space->id, 'created_by_user_id' => $user->id]);

    $this->actingAs($user)->post(route('goals.contribute', $goal), [
        'amount' => 100000,
        'wallet_id' => $foreignWallet->id,
    ])->assertSessionHasErrors('wallet_id');

    expect((float) $foreignWallet->fresh()->balance)->toBe(1000000.0)
        ->and((float) $goal->fresh()->current_amount)->toBe(0.0);
});

test('contribution cannot deduct money from partners personal wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $partnerWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'balance' => 1000000,
    ]);
    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'current_amount' => 0,
    ]);

    $this->actingAs($user)->post(route('goals.contribute', $goal), [
        'amount' => 100000,
        'wallet_id' => $partnerWallet->id,
    ])->assertSessionHasErrors('wallet_id');

    expect((float) $partnerWallet->fresh()->balance)->toBe(1000000.0)
        ->and((float) $goal->fresh()->current_amount)->toBe(0.0);
});

test('repeated savings contribution only deducts wallet once', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 1000000,
    ]);
    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'current_amount' => 0,
    ]);
    $payload = [
        'amount' => 100000,
        'wallet_id' => $wallet->id,
        'client_reference' => 'saving-request-123',
    ];

    $this->actingAs($user)->post(route('goals.contribute', $goal), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('goals.contribute', $goal), $payload)->assertRedirect();

    expect((float) $wallet->fresh()->balance)->toBe(900000.0)
        ->and((float) $goal->fresh()->current_amount)->toBe(100000.0)
        ->and(SavingsContribution::query()->where('client_reference', 'saving-request-123')->count())->toBe(1);
});

test('contribution cannot overdraw wallet balance', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 50000]);
    $goal = SavingsGoal::factory()->create(['couple_space_id' => $space->id, 'created_by_user_id' => $user->id]);

    $this->actingAs($user)->post(route('goals.contribute', $goal), [
        'amount' => 100000,
        'wallet_id' => $wallet->id,
    ])->assertSessionHasErrors('amount');

    expect((float) $wallet->fresh()->balance)->toBe(50000.0);
});

test('deleting goal refunds wallet-backed contributions', function () {
    $user = User::factory()->create();
    $space = CoupleSpace::factory()->create(['user_one_id' => $user->id]);
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 700000]);
    $goal = SavingsGoal::factory()->create(['couple_space_id' => $space->id, 'created_by_user_id' => $user->id, 'current_amount' => 300000]);
    SavingsContribution::create([
        'savings_goal_id' => $goal->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 300000,
        'contributed_at' => now(),
    ]);

    $this->actingAs($user)->delete(route('goals.destroy', $goal))->assertRedirect();

    expect((float) $wallet->fresh()->balance)->toBe(1000000.0);
});

test('partner cannot manage another users personal savings goal', function () {
    $space = CoupleSpace::factory()->active()->create();
    $owner = $space->userOne;
    $partner = $space->userTwo;
    $owner->update(['current_couple_space_id' => $space->id]);
    $partner->update(['current_couple_space_id' => $space->id]);
    $partnerWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'balance' => 500000,
    ]);
    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $owner->id,
        'scope' => 'personal',
        'current_amount' => 0,
    ]);

    $this->actingAs($partner)->post(route('goals.contribute', $goal), [
        'amount' => 100000,
        'wallet_id' => $partnerWallet->id,
        'client_reference' => 'partner-personal-goal',
    ])->assertForbidden();
    $this->actingAs($partner)->put(route('goals.update', $goal), [
        'name' => 'Diubah Pasangan',
        'target_amount' => 2000000,
    ])->assertForbidden();
    $this->actingAs($partner)->delete(route('goals.destroy', $goal))->assertForbidden();

    expect((float) $goal->fresh()->current_amount)->toBe(0.0)
        ->and((float) $partnerWallet->fresh()->balance)->toBe(500000.0);
});

test('personal savings cannot deduct a joint wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $jointWallet = Wallet::factory()->joint()->create([
        'couple_space_id' => $space->id,
        'balance' => 500000,
    ]);
    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'scope' => 'personal',
        'current_amount' => 0,
    ]);

    $this->actingAs($user)->post(route('goals.contribute', $goal), [
        'amount' => 100000,
        'wallet_id' => $jointWallet->id,
        'client_reference' => 'personal-from-joint-wallet',
    ])->assertSessionHasErrors('wallet_id');

    expect((float) $goal->fresh()->current_amount)->toBe(0.0)
        ->and((float) $jointWallet->fresh()->balance)->toBe(500000.0);
});

test('both partners can contribute to a shared savings goal', function () {
    $space = CoupleSpace::factory()->active()->create();
    $owner = $space->userOne;
    $partner = $space->userTwo;
    $owner->update(['current_couple_space_id' => $space->id]);
    $partner->update(['current_couple_space_id' => $space->id]);
    $partnerWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'balance' => 500000,
    ]);
    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $owner->id,
        'scope' => 'shared',
        'current_amount' => 0,
    ]);

    $this->actingAs($partner)->post(route('goals.contribute', $goal), [
        'amount' => 100000,
        'wallet_id' => $partnerWallet->id,
        'client_reference' => 'partner-shared-goal',
    ])->assertRedirect();

    expect((float) $goal->fresh()->current_amount)->toBe(100000.0)
        ->and((float) $partnerWallet->fresh()->balance)->toBe(400000.0);
});

test('savings goals mobile UI provides personal and shared choices', function () {
    $page = file_get_contents(resource_path('js/pages/Goals/Index.vue'));

    expect($page)
        ->toContain("scope: 'personal' as 'personal' | 'shared'")
        ->toContain('Total Tersimpan')
        ->toContain('Tujuan yang sedang kamu bangun')
        ->toContain('Jenis Tabungan')
        ->toContain('Pribadi')
        ->toContain('Bersama')
        ->toContain('canManageGoal(goal)')
        ->toContain("goal.scope === 'shared'")
        ->toContain('min-h-11');
});

test('savings page uses the shared compact mobile header', function () {
    $page = file_get_contents(resource_path('js/pages/Goals/Index.vue'));

    expect($page)
        ->toContain("import PageHeader from '@/components/PageHeader.vue'")
        ->toContain('title="Tabungan"')
        ->toContain('<ShoppingBag')
        ->toContain('aria-label="Buat target tabungan"');
});
