<?php

use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard renders the birthday surprise entry point', function () {
    $dashboard = file_get_contents(resource_path('js/pages/Dashboard.vue'));

    expect($dashboard)
        ->toContain("import BirthdaySurprise from '@/components/BirthdaySurprise.vue'")
        ->toContain('v-if="birthdaySurprise"')
        ->toContain(':surprise="birthdaySurprise"');
});

test('birthday surprise presents photos as an accessible cinematic gallery', function () {
    $surprise = file_get_contents(resource_path('js/components/BirthdaySurprise.vue'));

    expect($surprise)
        ->toContain('activePhotoIndex')
        ->toContain('showPreviousPhoto')
        ->toContain('showNextPhoto')
        ->toContain('Kenangan kita')
        ->toContain('image-orientation: from-image')
        ->toContain('aria-label="Lihat foto berikutnya"')
        ->toContain('finishPhotoSwipe')
        ->toContain('memory-backdrop-drift')
        ->toContain('surprise-content-next');
});

test('birthday surprise opens automatically only once per Jakarta calendar day', function () {
    $surprise = file_get_contents(resource_path('js/components/BirthdaySurprise.vue'));

    expect($surprise)
        ->toContain("timeZone: 'Asia/Jakarta'")
        ->toContain('window.localStorage.getItem(storageKey.value)')
        ->toContain('window.localStorage.setItem(storageKey.value, todayInJakarta)')
        ->toContain('lastAutomaticOpenDate === todayInJakarta')
        ->not->toContain('window.sessionStorage')
        ->not->toContain('setTimeout(openSurprise');
});

test('legacy success flash messages are shared with inertia pages', function () {
    $user = User::factory()->create();

    $this->withSession(['success' => 'Perubahan berhasil disimpan.'])
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('statusMessage.type', 'success')
            ->where('statusMessage.message', 'Perubahan berhasil disimpan.'));
});

test('dashboard provides a shortcut to the trip tracker', function () {
    $dashboard = file_get_contents(resource_path('js/pages/Dashboard.vue'));

    expect($dashboard)
        ->toContain(':href="tripsIndex()"')
        ->toContain('Perjalanan')
        ->toContain('Bagikan lokasi dan pantau perjalanan pasangan');
});

test('dashboard net worth includes money moved into savings goals', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 700000]);
    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'current_amount' => 300000,
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('totalNetWorth', 1000000)
        ->where('jointNetWorth', 300000));
});

test('dashboard reports todays spending for each partner', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 75000,
        'transaction_date' => now(),
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 125000,
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('dailySpending', 200000)
        ->where('dailySpendingByUser.user', 75000)
        ->where('dailySpendingByUser.partner', 125000));
});

test('dashboard reports monthly income for each partner', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'amount' => 750000,
        'transaction_date' => now(),
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'amount' => 1250000,
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('monthlyIncome', 2000000)
        ->where('monthlyIncomeByUser.user', 750000)
        ->where('monthlyIncomeByUser.partner', 1250000));
});

test('dashboard reconciles transfer fees as money out without counting transfer principal', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $source = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);
    $destination = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $source->id,
        'to_wallet_id' => $destination->id,
        'type' => 'transfer',
        'amount' => 500000,
        'fee_amount' => 2500,
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('monthlySpending', 2500)
        ->where('monthlyTransferFees', 2500)
        ->where('dailySpending', 2500)
        ->where('monthlySpendingByUser.user', 2500)
        ->where('spendingByScope.personal', 2500));
});
