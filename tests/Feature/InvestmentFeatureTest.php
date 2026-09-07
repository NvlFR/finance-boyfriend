<?php

use App\Models\CoupleSpace;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia as Assert;

function investmentSpace(): array
{
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $partner->update(['current_couple_space_id' => $space->id]);

    return [$space, $user, $partner];
}

test('user can open investment portfolio and create a personal asset', function () {
    [$space, $user] = investmentSpace();

    $this->actingAs($user)->get(route('investments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Investments/Index')
            ->has('investments', 0)
            ->where('summary.market_value', '0.00'));

    $this->actingAs($user)->post(route('investments.store'), [
        'name' => 'Saham BBCA',
        'symbol' => 'BBCA',
        'asset_type' => 'stock',
        'scope' => 'personal',
    ])->assertRedirect();

    $investment = Investment::query()->firstOrFail();
    expect($investment->couple_space_id)->toBe($space->id)
        ->and($investment->user_id)->toBe($user->id)
        ->and($investment->quantity)->toBe('0.00000000');
});

test('buying investment deducts wallet and calculates average price including fee', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '1000000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => 0,
        'average_buy_price' => 0,
        'current_price' => 0,
    ]);

    $this->actingAs($user)->post(route('investments.transact', $investment), [
        'type' => 'buy',
        'wallet_id' => $wallet->id,
        'quantity' => '2.5',
        'unit_price' => '100000',
        'fee_amount' => '5000',
        'transaction_date' => '2026-09-05',
        'client_reference' => 'buy-bbca-1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $investment->refresh();
    expect($wallet->fresh()->balance)->toBe('745000.00')
        ->and($investment->quantity)->toBe('2.50000000')
        ->and($investment->average_buy_price)->toBe('102000.00')
        ->and($investment->current_price)->toBe('100000.00')
        ->and(InvestmentTransaction::query()->count())->toBe(1);
});

test('user can buy investment using an exact rupiah amount', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '1000000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => 0,
        'average_buy_price' => 0,
        'current_price' => 0,
    ]);

    $this->actingAs($user)->post(route('investments.transact', $investment), [
        'type' => 'buy',
        'input_mode' => 'amount',
        'wallet_id' => $wallet->id,
        'amount' => '100000',
        'unit_price' => '1500',
        'fee_amount' => '2500',
        'transaction_date' => '2026-09-05',
        'client_reference' => 'rupiah-buy-1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $investment->refresh();
    $transaction = InvestmentTransaction::query()->sole();

    expect($wallet->fresh()->balance)->toBe('897500.00')
        ->and($investment->quantity)->toBe('66.66666666')
        ->and($investment->average_buy_price)->toBe('1537.50')
        ->and($investment->current_price)->toBe('1500.00')
        ->and($transaction->gross_amount)->toBe('100000.00')
        ->and($transaction->quantity)->toBe('66.66666666');
});

test('repeated investment request only mutates balance once', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '500000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => 0,
        'average_buy_price' => 0,
    ]);
    $payload = [
        'type' => 'buy',
        'wallet_id' => $wallet->id,
        'quantity' => '1',
        'unit_price' => '100000',
        'fee_amount' => '1000',
        'transaction_date' => '2026-09-05',
        'client_reference' => 'same-mobile-tap',
    ];

    $this->actingAs($user)->post(route('investments.transact', $investment), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('investments.transact', $investment), $payload)->assertRedirect();

    expect($wallet->fresh()->balance)->toBe('399000.00')
        ->and($investment->fresh()->quantity)->toBe('1.00000000')
        ->and(InvestmentTransaction::query()->count())->toBe(1);
});

test('selling investment adds net proceeds and records realized profit', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '100000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => '3.00000000',
        'average_buy_price' => '100000.00',
        'current_price' => '100000.00',
        'realized_profit_loss' => 0,
    ]);

    $this->actingAs($user)->post(route('investments.transact', $investment), [
        'type' => 'sell',
        'wallet_id' => $wallet->id,
        'quantity' => '1.5',
        'unit_price' => '120000',
        'fee_amount' => '2000',
        'transaction_date' => '2026-09-05',
        'client_reference' => 'sell-bbca-1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $investment->refresh();
    expect($wallet->fresh()->balance)->toBe('278000.00')
        ->and($investment->quantity)->toBe('1.50000000')
        ->and($investment->realized_profit_loss)->toBe('28000.00');
});

test('user cannot trade partners personal investment or wallet', function () {
    [$space, $user, $partner] = investmentSpace();
    $partnerWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'balance' => '500000.00',
    ]);
    $partnerInvestment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'scope' => 'personal',
        'quantity' => 0,
    ]);

    $this->actingAs($user)->post(route('investments.transact', $partnerInvestment), [
        'type' => 'buy',
        'wallet_id' => $partnerWallet->id,
        'quantity' => '1',
        'unit_price' => '100000',
        'transaction_date' => '2026-09-05',
        'client_reference' => 'forbidden-buy',
    ])->assertForbidden();

    expect($partnerWallet->fresh()->balance)->toBe('500000.00')
        ->and($partnerInvestment->fresh()->quantity)->toBe('0.00000000');
});

test('investment purchase cannot overdraw wallet', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '50000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => 0,
    ]);

    $this->actingAs($user)->post(route('investments.transact', $investment), [
        'type' => 'buy',
        'wallet_id' => $wallet->id,
        'quantity' => '1',
        'unit_price' => '100000',
        'fee_amount' => 0,
        'transaction_date' => '2026-09-05',
        'client_reference' => 'overdraw-buy',
    ])->assertSessionHasErrors('quantity');

    expect($wallet->fresh()->balance)->toBe('50000.00')
        ->and($investment->fresh()->quantity)->toBe('0.00000000');
});

test('investment sale cannot exceed available units', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '50000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => '1.00000000',
        'average_buy_price' => '100000.00',
    ]);

    $this->actingAs($user)->post(route('investments.transact', $investment), [
        'type' => 'sell',
        'wallet_id' => $wallet->id,
        'quantity' => '2',
        'unit_price' => '120000',
        'fee_amount' => 0,
        'transaction_date' => '2026-09-05',
        'client_reference' => 'oversell',
    ])->assertSessionHasErrors('quantity');

    expect($wallet->fresh()->balance)->toBe('50000.00')
        ->and($investment->fresh()->quantity)->toBe('1.00000000');
});

test('partner can manage a shared investment using a joint wallet', function () {
    [$space, $user, $partner] = investmentSpace();
    $wallet = Wallet::factory()->joint()->create([
        'couple_space_id' => $space->id,
        'balance' => '500000.00',
    ]);
    $investment = Investment::factory()->shared()->create([
        'couple_space_id' => $space->id,
        'quantity' => 0,
        'average_buy_price' => 0,
    ]);

    $this->actingAs($partner)->post(route('investments.transact', $investment), [
        'type' => 'buy',
        'wallet_id' => $wallet->id,
        'quantity' => '1',
        'unit_price' => '100000',
        'fee_amount' => 0,
        'transaction_date' => '2026-09-05',
        'client_reference' => 'shared-buy',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($wallet->fresh()->balance)->toBe('400000.00')
        ->and($investment->fresh()->quantity)->toBe('1.00000000')
        ->and(InvestmentTransaction::query()->value('user_id'))->toBe($partner->id);
});

test('buying investment does not reduce total net worth except its fee', function () {
    [$space, $user] = investmentSpace();
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => '1000000.00',
    ]);
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => '2.00000000',
        'average_buy_price' => '100000.00',
        'current_price' => '100000.00',
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalNetWorth', 1200000)
            ->where('userNetWorth', 1200000));
});

test('investment with remaining units cannot be deleted', function () {
    [$space, $user] = investmentSpace();
    $investment = Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'quantity' => '1.00000000',
    ]);

    $this->actingAs($user)->delete(route('investments.destroy', $investment))
        ->assertRedirect()
        ->assertSessionHasErrors('investment');

    $this->assertModelExists($investment);
});
