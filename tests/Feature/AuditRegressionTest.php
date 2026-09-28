<?php

use App\Models\CoupleSpace;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionService;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Process\Process;

test('history dates use Jakarta across midnight and device timezones', function () {
    $script = <<<'JS'
import assert from 'node:assert/strict';
import { jakartaDateKey } from './resources/js/lib/dates.ts';
assert.equal(jakartaDateKey('2026-09-19T23:00:00.000000Z'), '2026-09-20');
assert.equal(jakartaDateKey('2026-09-19T16:59:59.000000Z'), '2026-09-19');
assert.equal(jakartaDateKey('2026-09-19T17:00:00.000000Z'), '2026-09-20');
assert.equal(jakartaDateKey('2026-09-20'), '2026-09-20');
assert.equal(jakartaDateKey('2026-12-31T17:00:00Z'), '2027-01-01');
JS;
    $process = new Process(['node', '--experimental-strip-types', '--input-type=module', '-e', $script], base_path(), ['TZ' => 'America/Los_Angeles']);
    $process->run();
    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
});

beforeEach(function () {
    $this->space = CoupleSpace::factory()->create();
    $this->user = $this->space->userOne;
    $this->user->update(['current_couple_space_id' => $this->space->id]);
    $this->wallet = Wallet::factory()->create(['couple_space_id' => $this->space->id, 'user_id' => $this->user->id, 'type' => 'personal', 'balance' => 100000]);
    $this->actingAs($this->user);
});

test('pairing preserves investments and their trades', function () {
    $asset = Investment::factory()->create(['couple_space_id' => $this->space->id, 'user_id' => $this->user->id]);
    $trade = InvestmentTransaction::factory()->create(['investment_id' => $asset->id, 'user_id' => $this->user->id, 'wallet_id' => $this->wallet->id]);
    $destination = CoupleSpace::factory()->create();
    $this->postJson(route('couple-space.join'), ['invite_code' => $destination->invite_code])->assertOk();
    expect($asset->fresh()->couple_space_id)->toBe($destination->id);
    $this->assertModelExists($trade);
});

test('stale transaction delete cannot refund a wallet twice', function () {
    $service = app(TransactionService::class);
    $transaction = $service->createTransaction($this->user, $this->space, ['wallet_id' => $this->wallet->id, 'type' => 'expense', 'scope' => 'personal', 'amount' => 10000]);
    $stale = $transaction->fresh();
    $service->deleteTransaction($transaction);
    $service->deleteTransaction($stale);
    expect($this->wallet->fresh()->balance)->toBe('100000.00');
});

test('editing a spent income applies only the net difference even with a stale model', function () {
    $service = app(TransactionService::class);
    $income = $service->createTransaction($this->user, $this->space, ['wallet_id' => $this->wallet->id, 'type' => 'income', 'scope' => 'personal', 'amount' => 200000]);
    $service->createTransaction($this->user, $this->space, ['wallet_id' => $this->wallet->id, 'type' => 'expense', 'scope' => 'personal', 'amount' => 250000]);
    $stale = $income->fresh();
    $service->updateTransaction($income, ['title' => 'Gaji diperbaiki', 'amount' => 210000]);
    $service->updateTransaction($stale, ['title' => 'Gaji diperbaiki', 'amount' => 210000]);
    expect($this->wallet->fresh()->balance)->toBe('60000.00');
});

test('wallet detail edits preserve new transactions and stale adjustments are rejected', function () {
    $this->wallet->increment('balance', 50000);
    $this->putJson(route('wallets.update', $this->wallet), ['name' => 'Dompet Baru'])->assertOk();
    expect($this->wallet->fresh()->balance)->toBe('150000.00');
    $this->putJson(route('wallets.update', $this->wallet), ['balance' => 200000, 'expected_balance' => 100000])
        ->assertUnprocessable()->assertJsonValidationErrors('balance');
    $this->putJson(route('wallets.update', $this->wallet), ['balance' => 200000])
        ->assertUnprocessable()->assertJsonValidationErrors('expected_balance');
    expect($this->wallet->fresh()->balance)->toBe('150000.00');
});

test('used emergency savings cannot be deleted and no balances are refunded', function () {
    $goal = SavingsGoal::factory()->create(['couple_space_id' => $this->space->id, 'created_by_user_id' => $this->user->id, 'current_amount' => 50000, 'is_emergency_fund' => true]);
    app(TransactionService::class)->createTransaction($this->user, $this->space, ['emergency_savings_goal_id' => $goal->id, 'type' => 'expense', 'scope' => 'personal', 'amount' => 10000]);
    $this->deleteJson(route('goals.destroy', $goal))->assertUnprocessable()->assertJsonValidationErrors('goal');
    expect($goal->fresh()->current_amount)->toBe('40000.00')
        ->and($this->wallet->fresh()->balance)->toBe('100000.00');
});

test('history filters apply to investments and savings with access to older savings', function () {
    $goal = SavingsGoal::factory()->create(['couple_space_id' => $this->space->id, 'created_by_user_id' => $this->user->id, 'scope' => 'personal']);
    for ($i = 0; $i < 21; $i++) {
        SavingsContribution::create(['savings_goal_id' => $goal->id, 'user_id' => $this->user->id, 'wallet_id' => $this->wallet->id, 'amount' => 1000, 'contributed_at' => '2026-09-20 06:00:00']);
    }
    $asset = Investment::factory()->create(['couple_space_id' => $this->space->id, 'user_id' => $this->user->id]);
    InvestmentTransaction::factory()->create(['investment_id' => $asset->id, 'user_id' => $this->user->id, 'wallet_id' => $this->wallet->id, 'transaction_date' => '2026-09-20 06:00:00']);
    $this->getJson(route('transactions.index', ['start_date' => '2026-09-21']))->assertOk()
        ->assertJsonCount(0, 'savingsMovements')->assertJsonCount(0, 'investmentMovements.data');
    $this->getJson(route('transactions.index', ['scope' => 'shared']))->assertOk()
        ->assertJsonCount(0, 'savingsMovements')->assertJsonCount(0, 'investmentMovements.data');
    $this->getJson(route('transactions.index', ['savings_page' => 2]))->assertOk()->assertJsonCount(1, 'savingsMovements');
});

test('cumulative history reload includes earlier pages and reflects deleted rows', function () {
    Transaction::factory()->count(45)->create(['couple_space_id' => $this->space->id, 'wallet_id' => $this->wallet->id, 'user_id' => $this->user->id]);
    $url = route('transactions.index', ['page' => 2, 'cumulative' => true]);
    $this->getJson($url)->assertOk()->assertJsonCount(40, 'transactions.data');
    Transaction::query()->where('couple_space_id', $this->space->id)->first()->delete();
    $this->getJson($url)->assertOk()->assertJsonCount(40, 'transactions.data')->assertJsonPath('transactions.total', 44);
});

test('dashboard includes investment fees but excludes trade principal from cashflow', function () {
    $this->travelTo(now()->setDate(2026, 9, 20)->setTime(12, 0));
    $asset = Investment::factory()->create(['couple_space_id' => $this->space->id, 'user_id' => $this->user->id]);
    InvestmentTransaction::factory()->create(['investment_id' => $asset->id, 'user_id' => $this->user->id, 'wallet_id' => $this->wallet->id, 'gross_amount' => 100000, 'fee_amount' => 1500, 'transaction_date' => now()]);
    $this->get(route('dashboard', ['chart_period' => 'all']))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('monthlySpending', 1500)->where('dailySpending', 1500)->where('monthlyIncome', 0)
        ->where('chartSpendingTotal', 1500)->where('dailyTrend.0.expense', 1500)
        ->where('monthlySpendingByUser.user', 1500)->where('categorySpending.0.name', 'Biaya Investasi'));
});

test('icon only finance actions expose contextual accessible names', function () {
    $sources = [
        file_get_contents(resource_path('js/pages/Wishlists/Index.vue')),
        file_get_contents(resource_path('js/pages/Subscriptions/Index.vue')),
        file_get_contents(resource_path('js/pages/Budgets/Index.vue')),
        file_get_contents(resource_path('js/pages/Categories/Index.vue')),
        file_get_contents(resource_path('js/pages/Goals/Index.vue')),
        file_get_contents(resource_path('js/pages/Transactions/Index.vue')),
        file_get_contents(resource_path('js/pages/CoupleSpace/Index.vue')),
    ];

    expect($sources[0])->toContain(':aria-label="`Edit wishlist ${item.title}`"', ':aria-label="`Hapus wishlist ${item.title}`"')
        ->and($sources[1])->toContain(':aria-label="`Edit langganan ${sub.name}`"', ':aria-label="`Hapus langganan ${sub.name}`"')
        ->and($sources[2])->toContain(':aria-label="`Edit anggaran ${b.name}`"', ':aria-label="`Hapus anggaran ${b.name}`"')
        ->and($sources[3])->toContain(':aria-label="`Edit kategori ${cat.name}`"', ':aria-label="`Hapus kategori ${cat.name}`"')
        ->and($sources[4])->toContain(':aria-label="`Edit target ${goal.name}`"', ':aria-label="`Hapus target ${goal.name}`"')
        ->and($sources[5])->toContain(':aria-label="`Edit transaksi ${tx.title || \'tanpa judul\'}`"', ':aria-label="`Hapus transaksi ${tx.title || \'tanpa judul\'}`"')
        ->and($sources[6])->toContain('aria-label="Edit data ruang pasangan"');
});
