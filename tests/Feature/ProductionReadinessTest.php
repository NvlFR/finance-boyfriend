<?php

use App\Models\CoupleSpace;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\InvestmentService;
use App\Services\PushEndpointValidator;
use App\Services\SettlementService;
use App\Services\TransactionService;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\Process\Process;

function auditFixture(): array
{
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $partner->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 1000000]);

    return [$space, $user, $partner, $wallet];
}

function auditBuy(User $user, CoupleSpace $space, Wallet $wallet): Investment
{
    $investment = Investment::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'quantity' => 0]);
    app(InvestmentService::class)->recordTransaction($user, $investment, [
        'type' => 'buy', 'input_mode' => 'amount', 'amount' => 100000,
        'unit_price' => 1000, 'fee_amount' => 1500, 'wallet_id' => $wallet->id,
        'transaction_date' => now(), 'client_reference' => 'audit-buy',
    ]);

    return $investment->fresh();
}

test('archiving a sold investment preserves its financial history', function () {
    [$space, $user, , $wallet] = auditFixture();
    $investment = auditBuy($user, $space, $wallet);
    app(InvestmentService::class)->recordTransaction($user, $investment, [
        'type' => 'sell', 'input_mode' => 'quantity', 'quantity' => 100,
        'unit_price' => 1000, 'fee_amount' => 1500, 'wallet_id' => $wallet->id,
        'transaction_date' => now(), 'client_reference' => 'audit-sell',
    ]);
    expect(InvestmentTransaction::count())->toBe(2);
    $this->actingAs($user)->delete(route('investments.destroy', $investment))->assertRedirect()->assertSessionHasNoErrors();
    expect(InvestmentTransaction::count())->toBe(2)
        ->and($investment->fresh()->is_active)->toBeFalse()
        ->and($wallet->fresh()->balance)->toBe('997000.00');
});

test('wallet used for investment cannot be removed with remaining funds', function () {
    [$space, $user, , $wallet] = auditFixture();
    auditBuy($user, $space, $wallet);
    expect($wallet->fresh()->balance)->toBe('898500.00');
    $this->actingAs($user)->deleteJson(route('wallets.destroy', $wallet))->assertUnprocessable();
    expect($wallet->fresh()->balance)->toBe('898500.00')
        ->and(InvestmentTransaction::first()->wallet_id)->toBe($wallet->id);
});

test('deleting space creator is blocked to preserve partner finances', function () {
    [$space, $user, $partner] = auditFixture();
    $partnerWallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $partner->id, 'balance' => 254000]);
    $this->actingAs($user)->deleteJson(route('profile.destroy'), ['password' => 'password'])->assertUnprocessable();
    expect($partner->fresh())->not->toBeNull()
        ->and($space->fresh())->not->toBeNull()
        ->and($partnerWallet->fresh()->balance)->toBe('254000.00');
});

test('archived wallets cannot receive new transactions', function () {
    [$space, $user, , $wallet] = auditFixture();
    $wallet->update(['is_active' => false]);
    $this->actingAs($user)->postJson(route('transactions.store'), [
        'type' => 'income', 'scope' => 'personal', 'amount' => 50000,
        'wallet_id' => $wallet->id, 'transaction_date' => now()->toDateString(),
    ])->assertUnprocessable();
    expect($wallet->fresh()->balance)->toBe('1000000.00');
    $this->getJson(route('wallets.index'))->assertJsonPath('total_net_worth', 0);
});

test('PDF summary includes investment fees without treating purchases as expenses', function () {
    [$space, $user, , $wallet] = auditFixture();
    auditBuy($user, $space, $wallet);
    $this->actingAs($user)->get(route('transactions.export.pdf'))
        ->assertOk()->assertViewHas('summary', fn (array $summary): bool => $summary['outflow'] === 1500.0);
});

test('editing split expense recalculates equal shares', function () {
    [$space, $user, , $wallet] = auditFixture();
    $transaction = app(TransactionService::class)->createTransaction($user, $space, [
        'type' => 'expense', 'scope' => 'shared', 'amount' => 100000,
        'wallet_id' => $wallet->id, 'transaction_date' => now(), 'create_split' => true,
    ]);
    $this->actingAs($user)->putJson(route('transactions.update', $transaction), [
        'type' => 'expense', 'scope' => 'shared', 'amount' => 200000,
        'wallet_id' => $wallet->id, 'transaction_date' => now()->toDateString(),
    ])->assertOk();
    expect($transaction->fresh()->amount)->toBe('200000.00')
        ->and($transaction->fresh()->split->user_one_amount)->toBe('100000.00')
        ->and($transaction->fresh()->split->user_two_amount)->toBe('100000.00');
});

test('malformed split amount returns a validation error', function () {
    [, $user, , $wallet] = auditFixture();
    $this->actingAs($user)->postJson(route('transactions.store'), [
        'type' => 'expense', 'scope' => 'shared', 'amount' => 100,
        'wallet_id' => $wallet->id, 'transaction_date' => now()->toDateString(),
        'create_split' => true, 'split' => ['split_type' => 'custom', 'user_one_amount' => 'abc', 'user_two_amount' => 50],
    ])->assertUnprocessable();
});

test('private HTTP endpoints and invalid push keys are rejected', function () {
    [, $user] = auditFixture();
    $this->actingAs($user)->postJson(route('push.subscribe'), [
        'endpoint' => 'http://127.0.0.1:8080/internal',
        'public_key' => 'invalid-key', 'auth_token' => 'invalid-token',
    ])->assertUnprocessable();
});

test('Google sign in requires configured application two factor challenge', function () {
    $user = User::factory()->create([
        'google_id' => 'audit-google', 'two_factor_secret' => encrypt('AUDITSECRET'),
        'two_factor_recovery_codes' => encrypt(json_encode(['audit-recovery'])), 'two_factor_confirmed_at' => now(),
    ]);
    $googleUser = Mockery::mock(Laravel\Socialite\Two\User::class);
    $googleUser->shouldReceive('getId')->andReturn('audit-google');
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    $this->get(route('auth.google.callback'))->assertRedirect(route('two-factor.login'))->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
    $this->post(route('two-factor.login.store'), ['recovery_code' => 'audit-recovery'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('settlement transfer cannot be deleted leaving a false settled balance', function () {
    [$space, $user, $partner, $wallet] = auditFixture();
    $partnerWallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $partner->id, 'balance' => 100000]);
    $expense = app(TransactionService::class)->createTransaction($user, $space, [
        'type' => 'expense', 'scope' => 'shared', 'amount' => 100000,
        'wallet_id' => $wallet->id, 'transaction_date' => now(), 'create_split' => true,
    ]);
    $settlement = app(SettlementService::class)->settle($space, $partner, [
        'to_user_id' => $user->id, 'amount' => 50000, 'payment_method' => 'Transfer',
        'payment_mode' => 'wallet_transfer', 'source_wallet_id' => $partnerWallet->id,
        'destination_wallet_id' => $wallet->id, 'client_reference' => 'audit-settlement',
    ]);
    expect($partnerWallet->fresh()->balance)->toBe('50000.00');
    $this->actingAs($partner)->deleteJson(route('transactions.destroy', $settlement->transaction_id))->assertUnprocessable();
    expect($partnerWallet->fresh()->balance)->toBe('50000.00')
        ->and($expense->fresh()->split->settled)->toBeTrue()
        ->and($settlement->fresh()->transaction_id)->not->toBeNull();
});

test('unverified accounts must verify before accessing finances', function () {
    [, $user] = auditFixture();
    $user->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('CSV neutralizes user supplied spreadsheet formulas', function () {
    [$space, $user, , $wallet] = auditFixture();
    app(TransactionService::class)->createTransaction($user, $space, [
        'type' => 'expense', 'scope' => 'personal', 'amount' => 100,
        'wallet_id' => $wallet->id, 'transaction_date' => now(), 'title' => '=1+1',
    ]);
    $response = $this->actingAs($user)->get(route('transactions.export'))->assertOk();
    expect($response->streamedContent())->toContain("'=1+1");
});

test('refund to archived wallet makes the money visible again', function () {
    [$space, $user, , $wallet] = auditFixture();
    $transaction = app(TransactionService::class)->createTransaction($user, $space, [
        'type' => 'expense', 'scope' => 'personal', 'amount' => 1000000,
        'wallet_id' => $wallet->id, 'transaction_date' => now(),
    ]);
    $this->actingAs($user)->delete(route('wallets.destroy', $wallet))->assertSessionHasNoErrors();
    expect($wallet->fresh()->is_active)->toBeFalse();
    $this->deleteJson(route('transactions.destroy', $transaction))->assertSuccessful();
    expect($wallet->fresh()->is_active)->toBeTrue()
        ->and($wallet->fresh()->balance)->toBe('1000000.00');
});

test('archived investment realized profit remains in portfolio summary', function () {
    [$space, $user] = auditFixture();
    Investment::factory()->create([
        'couple_space_id' => $space->id, 'user_id' => $user->id,
        'quantity' => 0, 'realized_profit_loss' => 12000, 'is_active' => false,
    ]);
    $this->actingAs($user)->get(route('investments.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.realized_profit_loss', '12000.00')->has('investments', 0));
});

test('push endpoint allowlist rejects internal hosts and lookalikes', function (string $endpoint) {
    expect(PushEndpointValidator::allows($endpoint))->toBeFalse();
})->with([
    'https://127.0.0.1/internal', 'https://[::1]/internal',
    'https://169.254.169.254/latest/meta-data', 'https://fcm.googleapis.com.attacker.example/send',
    'https://fcm.googleapis.com@localhost/send', 'https://fcm.googleapis.com:8443/send',
    'https://evilpush.apple.com/send',
]);

test('transaction date input uses Jakarta even when device timezone differs', function (string $timezone) {
    $script = <<<'JS'
import fs from 'node:fs';
import assert from 'node:assert/strict';
import ts from 'typescript';
const source = fs.readFileSync('resources/js/lib/dates.ts', 'utf8');
const code = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ESNext } }).outputText;
const { jakartaDateKey, jakartaDateTimeInput } = await import('data:text/javascript;base64,' + Buffer.from(code).toString('base64'));
assert.equal(jakartaDateTimeInput(new Date('2026-09-09T23:00:00Z')), '2026-09-10T06:00');
assert.equal(jakartaDateTimeInput(new Date('2026-09-09T17:00:00Z')), '2026-09-10T00:00');
assert.equal(jakartaDateKey(new Date('2026-09-09T23:00:00Z')), '2026-09-10');
assert.equal(jakartaDateKey('2026-09-09'), '2026-09-09');
JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path(), ['TZ' => $timezone]);
    $process->run();
    expect($process->getErrorOutput())->toBe('')
        ->and($process->isSuccessful())->toBeTrue();
})->with(['UTC', 'America/Los_Angeles', 'Asia/Tokyo']);
