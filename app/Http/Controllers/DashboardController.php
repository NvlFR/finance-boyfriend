<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    /**
     * Display the main Couple Finance Dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            $categories = Category::whereNull('couple_space_id')->get();

            return Inertia::render('Dashboard', [
                'hasCoupleSpace' => false,
                'coupleSpace' => null,
                'partner' => null,
                'wallets' => [],
                'userWallets' => [],
                'partnerWallets' => [],
                'jointWallets' => [],
                'totalNetWorth' => 0,
                'userNetWorth' => 0,
                'partnerNetWorth' => 0,
                'jointNetWorth' => 0,
                'recentTransactions' => [],
                'settlementDebt' => null,
                'monthlySpending' => 0,
                'monthlyIncome' => 0,
                'categories' => $categories,
            ]);
        }

        $space->load(['userOne', 'userTwo']);
        $partner = $space->getPartnerOf($user);

        // Wallets
        $wallets = Wallet::where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->get();

        $userWallets = $wallets->where('user_id', $user->id);
        $partnerWallets = $partner ? $wallets->where('user_id', $partner->id) : collect();
        $jointWallets = $wallets->where('type', 'joint');

        $totalNetWorth = (float) $wallets->sum('balance');
        $userNetWorth = (float) $userWallets->sum('balance');
        $partnerNetWorth = (float) $partnerWallets->sum('balance');
        $jointNetWorth = (float) $jointWallets->sum('balance');

        // Recent Transactions
        $recentTransactions = Transaction::where('couple_space_id', $space->id)
            ->with(['wallet', 'toWallet', 'category', 'split', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Settlement debt status
        $settlementDebt = $this->settlementService->getUnsettledBalance($space);

        // Monthly Stats (Current Month)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlySpending = (float) Transaction::where('couple_space_id', $space->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyIncome = (float) Transaction::where('couple_space_id', $space->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Categories for quick add
        $categories = Category::where(function ($q) use ($space) {
            $q->whereNull('couple_space_id')
                ->orWhere('couple_space_id', $space->id);
        })->get();

        return Inertia::render('Dashboard', [
            'hasCoupleSpace' => true,
            'coupleSpace' => $space,
            'partner' => $partner,
            'wallets' => $wallets,
            'userWallets' => $userWallets->values(),
            'partnerWallets' => $partnerWallets->values(),
            'jointWallets' => $jointWallets->values(),
            'totalNetWorth' => $totalNetWorth,
            'userNetWorth' => $userNetWorth,
            'partnerNetWorth' => $partnerNetWorth,
            'jointNetWorth' => $jointNetWorth,
            'recentTransactions' => $recentTransactions,
            'settlementDebt' => $settlementDebt,
            'monthlySpending' => $monthlySpending,
            'monthlyIncome' => $monthlyIncome,
            'categories' => $categories,
        ]);
    }
}
