<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoupleAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Couple Users
        $boy = User::updateOrCreate(
            ['email' => 'rony@example.com'],
            [
                'name' => 'Rony',
                'nickname' => 'Rony',
                'password' => Hash::make('password'),
                'theme_color' => '#6366F1',
                'email_verified_at' => now(),
            ]
        );

        $girl = User::updateOrCreate(
            ['email' => 'sarah@example.com'],
            [
                'name' => 'Sarah',
                'nickname' => 'Sarah',
                'password' => Hash::make('password'),
                'theme_color' => '#F43F5E',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Active Couple Space
        $space = CoupleSpace::updateOrCreate(
            ['invite_code' => 'COUPLE26'],
            [
                'name' => 'Rony & Sarah Space',
                'user_one_id' => $boy->id,
                'user_two_id' => $girl->id,
                'status' => 'active',
                'anniversary_date' => '2023-08-20',
            ]
        );

        $boy->update(['current_couple_space_id' => $space->id]);
        $girl->update(['current_couple_space_id' => $space->id]);

        // 3. Create Wallets
        // Rony's Wallets
        $walletBcaBoy = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'name' => 'BCA Utama Rony',
            'type' => 'personal',
            'wallet_type' => 'bank',
            'account_number' => '541289001',
            'balance' => 15000000.00,
            'currency' => 'IDR',
            'color' => '#6366F1',
            'icon' => 'landmark',
            'is_active' => true,
        ]);

        $walletGopayBoy = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'name' => 'GoPay Rony',
            'type' => 'personal',
            'wallet_type' => 'ewallet',
            'account_number' => '081234567890',
            'balance' => 750000.00,
            'currency' => 'IDR',
            'color' => '#3B82F6',
            'icon' => 'smartphone',
            'is_active' => true,
        ]);

        // Sarah's Wallets
        $walletBcaGirl = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => $girl->id,
            'name' => 'BCA Sarah',
            'type' => 'personal',
            'wallet_type' => 'bank',
            'account_number' => '541289002',
            'balance' => 12500000.00,
            'currency' => 'IDR',
            'color' => '#EC4899',
            'icon' => 'landmark',
            'is_active' => true,
        ]);

        $walletShopeeGirl = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => $girl->id,
            'name' => 'ShopeePay Sarah',
            'type' => 'personal',
            'wallet_type' => 'ewallet',
            'account_number' => '081298765432',
            'balance' => 500000.00,
            'currency' => 'IDR',
            'color' => '#F59E0B',
            'icon' => 'smartphone',
            'is_active' => true,
        ]);

        // Joint Wallets
        $walletJointDate = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => null,
            'name' => 'Kas Kencan & Makan Luar',
            'type' => 'joint',
            'wallet_type' => 'bank',
            'account_number' => 'Rekening Bersama BCA',
            'balance' => 3500000.00,
            'currency' => 'IDR',
            'color' => '#10B981',
            'icon' => 'wallet',
            'is_active' => true,
        ]);

        $walletJointNikah = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => null,
            'name' => 'Tabungan Wedding Bersama',
            'type' => 'joint',
            'wallet_type' => 'investment',
            'account_number' => 'Bibit Reksa Dana',
            'balance' => 25000000.00,
            'currency' => 'IDR',
            'color' => '#14B8A6',
            'icon' => 'coins',
            'is_active' => true,
        ]);

        // 4. Sample Categories
        $catDating = Category::where('name', 'Makan & Kencan')->first();
        $catCinema = Category::where('name', 'Nonton & Hiburan')->first();
        $catGroceries = Category::where('name', 'Belanja & Groceries')->first();
        $catSalary = Category::where('name', 'Gaji Pokok')->first();

        // 5. Sample Transactions
        // Income
        Transaction::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'wallet_id' => $walletBcaBoy->id,
            'category_id' => $catSalary?->id,
            'type' => 'income',
            'scope' => 'personal',
            'amount' => 15000000.00,
            'transaction_date' => now()->startOfMonth(),
            'title' => 'Gaji Pokok Bulan Ini',
        ]);

        // Shared Date Expense (Split 50:50 - Rony paid 350k)
        $txDate = Transaction::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'wallet_id' => $walletBcaBoy->id,
            'category_id' => $catDating?->id,
            'type' => 'expense',
            'scope' => 'shared',
            'amount' => 350000.00,
            'transaction_date' => now()->subDays(2),
            'title' => 'Dinner Romantis di Resto Italia',
            'notes' => 'Makanannya enak banget, pasta & tiramisu juara! ❤️',
        ]);

        TransactionSplit::create([
            'transaction_id' => $txDate->id,
            'paid_by_user_id' => $boy->id,
            'user_one_amount' => 175000.00,
            'user_two_amount' => 175000.00,
            'split_type' => 'split_equal',
            'settled' => false,
        ]);

        // Shared Cinema Expense (Split 50:50 - Rony paid 150k)
        $txCinema = Transaction::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'wallet_id' => $walletGopayBoy->id,
            'category_id' => $catCinema?->id,
            'type' => 'expense',
            'scope' => 'shared',
            'amount' => 150000.00,
            'transaction_date' => now()->subDay(),
            'title' => 'Tiket Bioskop IMAX Premiere',
            'notes' => 'Film seru banget!',
        ]);

        TransactionSplit::create([
            'transaction_id' => $txCinema->id,
            'paid_by_user_id' => $boy->id,
            'user_one_amount' => 75000.00,
            'user_two_amount' => 75000.00,
            'split_type' => 'split_equal',
            'settled' => false,
        ]);

        // 6. Sample Savings Goals
        $goalJapan = SavingsGoal::create([
            'couple_space_id' => $space->id,
            'created_by_user_id' => $boy->id,
            'name' => 'Trip ke Jepang (Tokyo & Kyoto)',
            'target_amount' => 40000000.00,
            'current_amount' => 15000000.00,
            'target_date' => '2027-04-15',
            'icon' => 'plane',
            'color' => '#F43F5E',
            'status' => 'in_progress',
        ]);

        SavingsContribution::create([
            'savings_goal_id' => $goalJapan->id,
            'user_id' => $boy->id,
            'wallet_id' => $walletBcaBoy->id,
            'amount' => 10000000.00,
            'notes' => 'Setoran awal Rony',
            'contributed_at' => now()->subDays(10),
        ]);

        SavingsContribution::create([
            'savings_goal_id' => $goalJapan->id,
            'user_id' => $girl->id,
            'wallet_id' => $walletBcaGirl->id,
            'amount' => 5000000.00,
            'notes' => 'Setoran Sarah',
            'contributed_at' => now()->subDays(5),
        ]);

        // 7. Sample Wishlists
        Wishlist::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'title' => 'Espresso Machine Delonghi',
            'estimated_price' => 2800000.00,
            'priority' => 'high',
            'notes' => 'Biar bisa ngopi hemat berdua tiap pagi',
            'is_secret_surprise' => false,
            'is_bought' => false,
        ]);

        Wishlist::create([
            'couple_space_id' => $space->id,
            'user_id' => $boy->id,
            'title' => 'Kalung Berlian Simple Tiffany & Co',
            'estimated_price' => 5500000.00,
            'priority' => 'high',
            'notes' => 'Kado Anniversary ke-3 Sarah ❤️',
            'is_secret_surprise' => true,
            'target_user_id' => $girl->id,
            'is_bought' => false,
        ]);

        // 8. Sample Subscriptions
        Subscription::create([
            'couple_space_id' => $space->id,
            'paid_by_user_id' => $boy->id,
            'wallet_id' => $walletBcaBoy->id,
            'name' => 'Netflix Premium 4K Family',
            'amount' => 186000.00,
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(12)->toDateString(),
            'split_mode' => '50_50',
            'color' => '#E50914',
            'is_active' => true,
        ]);

        Subscription::create([
            'couple_space_id' => $space->id,
            'paid_by_user_id' => $girl->id,
            'wallet_id' => $walletBcaGirl->id,
            'name' => 'Spotify Premium Duo',
            'amount' => 71000.00,
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(18)->toDateString(),
            'split_mode' => '50_50',
            'color' => '#1DB954',
            'is_active' => true,
        ]);

        // 9. Sample Budget
        Budget::create([
            'couple_space_id' => $space->id,
            'category_id' => $catDating?->id,
            'name' => 'Budget Kencan & Makan Luar',
            'limit_amount' => 2500000.00,
            'period' => 'monthly',
            'scope' => 'shared',
            'user_id' => null,
        ]);
    }
}
