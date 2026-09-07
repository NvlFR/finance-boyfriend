<?php

use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

afterEach(function () {
    Carbon::setTestNow();
});

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

test('dashboard attributes personal and shared savings to the correct owners', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);

    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'scope' => 'personal',
        'current_amount' => 100000,
    ]);
    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $partner->id,
        'scope' => 'personal',
        'current_amount' => 200000,
    ]);
    SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'scope' => 'shared',
        'current_amount' => 300000,
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('totalNetWorth', 600000)
        ->where('userNetWorth', 100000)
        ->where('partnerNetWorth', 200000)
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

test('dashboard uses a seven day chart period by default', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 12:00:00'));
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->where('chartPeriod', '7d')
        ->where('chartPeriodLabel', '7 Hari')
        ->has('dailyTrend', 7)
        ->where('dailyTrend.0.date', '01 Sep')
        ->where('dailyTrend.6.date', '07 Sep'));
});

test('dashboard filters both charts to the last thirty days', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 12:00:00'));
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $category = Category::factory()->create(['name' => 'Belanja']);
    $source = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);
    $destination = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $source->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'scope' => 'shared',
        'amount' => 100000,
        'transaction_date' => now()->subDays(20),
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $source->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'scope' => 'shared',
        'amount' => 900000,
        'transaction_date' => now()->subDays(30),
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $source->id,
        'to_wallet_id' => $destination->id,
        'category_id' => null,
        'type' => 'transfer',
        'amount' => 500000,
        'fee_amount' => 2500,
        'transaction_date' => now(),
    ]);

    $this->actingAs($user)->get(route('dashboard', ['chart_period' => '30d']))->assertInertia(fn (Assert $page) => $page
        ->where('chartPeriod', '30d')
        ->where('chartPeriodLabel', '30 Hari')
        ->has('dailyTrend', 30)
        ->where('dailyTrend.29.expense', 2500)
        ->where('chartSpendingTotal', 102500)
        ->where('chartSpendingByScope.shared', 100000)
        ->where('chartSpendingByScope.personal', 2500)
        ->has('categorySpending', 2)
        ->where('categorySpending.0.total', 100000)
        ->where('categorySpending.1.total', 2500));
});

test('dashboard month chart includes only the current month through today', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-20 12:00:00'));
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 75000,
        'transaction_date' => Carbon::parse('2026-09-01 09:00:00'),
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 500000,
        'transaction_date' => Carbon::parse('2026-08-31 09:00:00'),
    ]);

    $this->actingAs($user)->get(route('dashboard', ['chart_period' => 'month']))->assertInertia(fn (Assert $page) => $page
        ->where('chartPeriod', 'month')
        ->where('chartPeriodLabel', 'Bulan Ini')
        ->has('dailyTrend', 20)
        ->where('dailyTrend.0.date', '01 Sep')
        ->where('chartSpendingTotal', 75000));
});

test('dashboard falls back to the seven day chart for an invalid period', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 12:00:00'));
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $this->actingAs($user)->get(route('dashboard', ['chart_period' => 'year']))->assertInertia(fn (Assert $page) => $page
        ->where('chartPeriod', '7d')
        ->where('chartPeriodLabel', '7 Hari')
        ->has('dailyTrend', 7));
});

test('dashboard chart filter updates both chart datasets without a full page reload', function () {
    $dashboard = file_get_contents(resource_path('js/pages/Dashboard.vue'));

    expect($dashboard)
        ->toContain("{ label: '7 Hari', value: '7d' }")
        ->toContain("{ label: '30 Hari', value: '30d' }")
        ->toContain("{ label: 'Bulan Ini', value: 'month' }")
        ->toContain("'dailyTrend'")
        ->toContain("'categorySpending'")
        ->toContain('preserveScroll: true')
        ->toContain('preserveState: true');
});
