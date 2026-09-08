<?php

use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\Investment;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use App\Models\Wallet;

test('user can list transactions with filters', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);

    $category = Category::factory()->create();

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'scope' => 'personal',
        'type' => 'expense',
    ]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'scope' => 'shared',
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('transactions.index', ['scope' => 'shared']));

    $response->assertOk()
        ->assertJsonCount(1, 'transactions.data');
});

test('transaction history exposes wallet movements to savings goals', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);
    $goal = SavingsGoal::factory()->create([
        'couple_space_id' => $space->id,
        'created_by_user_id' => $user->id,
        'name' => 'Dana Nikah',
    ]);
    SavingsContribution::create([
        'savings_goal_id' => $goal->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 250000,
        'contributed_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson(route('transactions.index'))
        ->assertOk()
        ->assertJsonPath('savingsMovements.0.goal.name', 'Dana Nikah')
        ->assertJsonPath('savingsMovements.0.wallet.user.name', $user->name)
        ->assertJsonPath('savingsMovements.0.amount', '250000.00');
});

test('transaction history mobile UI groups dates and keeps report exports', function () {
    $page = file_get_contents(resource_path('js/pages/Transactions/Index.vue'));

    expect($page)
        ->toContain('groupedTransactions')
        ->toContain('formatDateHeading')
        ->toContain("return 'Hari ini'")
        ->toContain("return 'Kemarin'")
        ->toContain('showExports')
        ->toContain('Laporan PDF')
        ->toContain('Excel')
        ->toContain('CSV');
});

test('user can store income and balance increments', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 100000,
    ]);

    $category = Category::factory()->create(['type' => 'income']);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'income',
            'scope' => 'personal',
            'amount' => 500000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Monthly Salary',
        ]);

    $response->assertCreated()
        ->assertJsonPath('transaction.amount', '500000.00');

    expect($wallet->fresh()->balance)->toBe('600000.00');
});

test('user can store expense and balance decrements', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 500000,
    ]);

    $category = Category::factory()->create(['type' => 'expense']);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'scope' => 'personal',
            'amount' => 150000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Dinner',
        ]);

    $response->assertCreated();
    expect($wallet->fresh()->balance)->toBe('350000.00');
});

test('user can store transfer between wallets', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $source = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 500000,
    ]);

    $dest = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 100000,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $source->id,
            'to_wallet_id' => $dest->id,
            'type' => 'transfer',
            'scope' => 'personal',
            'amount' => 200000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Transfer to savings',
        ]);

    $response->assertCreated();
    expect($source->fresh()->balance)->toBe('300000.00')
        ->and($dest->fresh()->balance)->toBe('300000.00');
});

test('transfer fee is deducted only from source wallet with exact cents', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $source = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 500000,
    ]);
    $destination = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 100000,
    ]);

    $response = $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $source->id,
        'to_wallet_id' => $destination->id,
        'type' => 'transfer',
        'scope' => 'personal',
        'amount' => '100000.50',
        'fee_amount' => '2500.25',
        'transaction_date' => now()->toIso8601String(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('transaction.fee_amount', '2500.25');

    expect($source->fresh()->balance)->toBe('397499.25')
        ->and($destination->fresh()->balance)->toBe('200000.50');
});

test('transfer never inherits an expense category and keeps its selected scope', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $category = Category::factory()->create(['type' => 'expense']);
    $source = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 500000]);
    $destination = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 100000]);

    $response = $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $source->id,
        'to_wallet_id' => $destination->id,
        'category_id' => $category->id,
        'type' => 'transfer',
        'scope' => 'personal',
        'amount' => 100000,
        'transaction_date' => now()->toIso8601String(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('transaction.category_id', null)
        ->assertJsonPath('transaction.scope', 'personal');
});

test('repeated transaction request only changes wallet balance once', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 500000]);
    $payload = [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'scope' => 'personal',
        'amount' => 100000,
        'transaction_date' => now()->toIso8601String(),
        'client_reference' => 'mobile-request-123',
    ];

    $this->actingAs($user)->postJson(route('transactions.store'), $payload)->assertCreated();
    $this->actingAs($user)->postJson(route('transactions.store'), $payload)->assertCreated();

    expect($wallet->fresh()->balance)->toBe('400000.00');
    $this->assertDatabaseCount('transactions', 1);
});

test('storing shared expense creates transaction split record automatically', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 1000000,
    ]);

    $category = Category::factory()->create(['type' => 'expense']);

    $response = $this->actingAs($user)
        ->postJson(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'scope' => 'shared',
            'amount' => 200000,
            'transaction_date' => now()->toIso8601String(),
            'title' => 'Romantic Dinner',
            'split' => [
                'paid_by_user_id' => $user->id,
                'split_type' => 'split_equal',
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('transaction.split.user_one_amount', '100000.00')
        ->assertJsonPath('transaction.split.user_two_amount', '100000.00')
        ->assertJsonPath('transaction.split.settled', false);

    $this->assertDatabaseHas('transaction_splits', [
        'user_one_amount' => 100000,
        'user_two_amount' => 100000,
        'settled' => false,
    ]);
});

test('destroying transaction rolls back wallet balance', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 800000,
    ]);

    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 200000,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson(route('transactions.destroy', $transaction));

    $response->assertOk();
    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    expect($wallet->fresh()->balance)->toBe('1000000.00');
});

test('user can update a transaction and balance is correctly recalculated', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'balance' => 800000, // after 200k expense from 1000k
    ]);

    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 200000,
        'title' => 'Initial Expense',
    ]);

    $response = $this->actingAs($user)
        ->putJson(route('transactions.update', $transaction), [
            'wallet_id' => $wallet->id,
            'title' => 'Updated Expense',
            'type' => 'expense',
            'scope' => 'personal',
            'amount' => 300000, // increasing expense to 300k, wallet should be 700k
            'transaction_date' => now()->toDateString(),
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'title' => 'Updated Expense',
        'amount' => 300000,
    ]);

    expect($wallet->fresh()->balance)->toBe('700000.00');
});

test('user can export transactions to CSV', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);

    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);

    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'title' => 'Exportable Dinner',
        'amount' => 150000,
    ]);

    $response = $this->actingAs($user)
        ->get(route('transactions.export'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())
        ->toContain('Exportable Dinner')
        ->toContain('Biaya Admin (Rp)');
});

test('user can export filtered transactions to Excel and a complete HTML financial report', function () {
    $space = CoupleSpace::factory()->active()->create(['name' => 'Ruang Keuangan']);
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
    ]);
    Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'title' => 'Laporan Test',
    ]);
    Investment::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'name' => 'Emas Laporan',
        'quantity' => 2,
        'average_buy_price' => 900000,
        'current_price' => 1000000,
    ]);

    $excel = $this->actingAs($user)->get(route('transactions.export.excel'));
    $excel->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($excel->getContent())->toStartWith('PK');
    $temporaryExcelPath = tempnam(sys_get_temp_dir(), 'excel-test-');
    file_put_contents($temporaryExcelPath, $excel->getContent());
    $archive = new ZipArchive;
    expect($archive->open($temporaryExcelPath))->toBeTrue()
        ->and($archive->getFromName('xl/worksheets/sheet1.xml'))->toContain('Laporan Test');
    $archive->close();
    unlink($temporaryExcelPath);

    $pdf = $this->actingAs($user)->get(route('transactions.export.pdf'));
    $pdf->assertOk()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertSee('Laporan Keuangan Lengkap')
        ->assertSee('Ringkasan Arus Kas')
        ->assertSee('Posisi Keuangan')
        ->assertSee('Anggaran Harian & Bulanan', false)
        ->assertSee('Emas Laporan')
        ->assertSee('Laporan Test');
});

test('transaction drawer defaults to personal scope and explains transfer fees', function () {
    $drawer = file_get_contents(resource_path('js/components/TransactionDrawer.vue'));

    expect($drawer)
        ->toContain("scope: 'personal'")
        ->toContain('fee_amount')
        ->toContain('transferSourceDebit')
        ->toContain('Biaya Admin')
        ->not->toContain('Kencan Bersama');
});

test('transaction cannot use wallet from another couple space', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $foreignWallet = Wallet::factory()->create(['balance' => 500000]);

    $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $foreignWallet->id,
        'type' => 'expense',
        'scope' => 'personal',
        'amount' => 100000,
        'transaction_date' => now()->toIso8601String(),
    ])->assertUnprocessable()->assertJsonValidationErrorFor('wallet_id');

    expect($foreignWallet->fresh()->balance)->toBe('500000.00');
});

test('transaction cannot deduct money from partners personal wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $partner = $space->userTwo;
    $user->update(['current_couple_space_id' => $space->id]);
    $partnerWallet = Wallet::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $partner->id,
        'balance' => 500000,
    ]);

    $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $partnerWallet->id,
        'type' => 'expense',
        'scope' => 'personal',
        'amount' => 100000,
        'transaction_date' => now()->toIso8601String(),
    ])->assertUnprocessable()->assertJsonValidationErrorFor('wallet_id');

    expect($partnerWallet->fresh()->balance)->toBe('500000.00');
});

test('shared custom split must equal transaction amount', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 500000]);

    $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'scope' => 'shared',
        'amount' => 100000,
        'transaction_date' => now()->toIso8601String(),
        'split' => [
            'paid_by_user_id' => $user->id,
            'split_type' => 'custom',
            'user_one_amount' => 10000,
            'user_two_amount' => 20000,
        ],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('split');

    expect($wallet->fresh()->balance)->toBe('500000.00');
});

test('expense cannot overdraw its wallet', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 50000]);

    $this->actingAs($user)->postJson(route('transactions.store'), [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'scope' => 'personal',
        'amount' => 100000,
        'transaction_date' => now()->toIso8601String(),
    ])->assertUnprocessable()->assertJsonValidationErrorFor('amount');

    expect($wallet->fresh()->balance)->toBe('50000.00');
});

test('updating shared expense without split input preserves original split', function () {
    $space = CoupleSpace::factory()->active()->create();
    $user = $space->userOne;
    $user->update(['current_couple_space_id' => $space->id]);
    $wallet = Wallet::factory()->create(['couple_space_id' => $space->id, 'user_id' => $user->id, 'balance' => 800000]);
    $transaction = Transaction::factory()->create([
        'couple_space_id' => $space->id,
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'scope' => 'shared',
        'amount' => 200000,
    ]);
    $split = TransactionSplit::factory()->create([
        'transaction_id' => $transaction->id,
        'paid_by_user_id' => $user->id,
        'split_type' => 'full_two',
        'user_one_amount' => 0,
        'user_two_amount' => 200000,
    ]);

    $this->actingAs($user)->putJson(route('transactions.update', $transaction), [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'scope' => 'shared',
        'amount' => 200000,
        'transaction_date' => now()->toIso8601String(),
    ])->assertOk();

    expect($split->fresh()->split_type)->toBe('full_two')
        ->and((float) $split->fresh()->user_two_amount)->toBe(200000.0);
});
