<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CoupleSpaceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Couple Space routes
    Route::get('couple-space', [CoupleSpaceController::class, 'index'])->name('couple-space.index');
    Route::post('couple-space', [CoupleSpaceController::class, 'store'])->name('couple-space.store');
    Route::post('couple-space/join', [CoupleSpaceController::class, 'join'])->name('couple-space.join');
    Route::get('couple-space/{coupleSpace?}', [CoupleSpaceController::class, 'show'])->name('couple-space.show');
    Route::put('couple-space/{coupleSpace}', [CoupleSpaceController::class, 'update'])->name('couple-space.update');

    // Wallet routes
    Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');

    // Transaction routes
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // Settlement routes
    Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
    Route::post('settlements', [SettlementController::class, 'store'])->name('settlements.store');

    // Category routes
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Savings Goals routes
    Route::get('goals', [SavingsGoalController::class, 'index'])->name('goals.index');
    Route::post('goals', [SavingsGoalController::class, 'store'])->name('goals.store');
    Route::put('goals/{savingsGoal}', [SavingsGoalController::class, 'update'])->name('goals.update');
    Route::post('goals/{savingsGoal}/contribute', [SavingsGoalController::class, 'contribute'])->name('goals.contribute');
    Route::delete('goals/{savingsGoal}', [SavingsGoalController::class, 'destroy'])->name('goals.destroy');

    // Wishlists routes
    Route::get('wishlists', [WishlistController::class, 'index'])->name('wishlists.index');
    Route::post('wishlists', [WishlistController::class, 'store'])->name('wishlists.store');
    Route::put('wishlists/{wishlist}', [WishlistController::class, 'update'])->name('wishlists.update');
    Route::patch('wishlists/{wishlist}/toggle', [WishlistController::class, 'toggleBought'])->name('wishlists.toggle');
    Route::delete('wishlists/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlists.destroy');

    // Subscriptions routes
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');

    // Budgets routes
    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
});

require __DIR__.'/settings.php';
