<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Investment;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\BirthdaySurpriseService;
use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** @var list<string> */
    private const CHART_PERIODS = ['7d', '30d', 'month'];

    public function __construct(
        protected SettlementService $settlementService,
        protected BirthdaySurpriseService $birthdaySurpriseService,
    ) {}

    /**
     * Display the main Couple Finance Dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;
        [$chartPeriod, $chartPeriodLabel, $chartStartDate, $chartEndDate] = $this->resolveChartPeriod($request);

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
                'monthlyTransferFees' => 0,
                'dailySpending' => 0,
                'dailySpendingByUser' => ['user' => 0, 'partner' => 0],
                'monthlySpendingByUser' => ['user' => 0, 'partner' => 0],
                'monthlyIncomeByUser' => ['user' => 0, 'partner' => 0],
                'categories' => $categories,
                'dailyTrend' => [],
                'categorySpending' => [],
                'spendingByScope' => ['shared' => 0, 'personal' => 0],
                'chartPeriod' => $chartPeriod,
                'chartPeriodLabel' => $chartPeriodLabel,
                'chartSpendingTotal' => 0,
                'chartSpendingByScope' => ['shared' => 0, 'personal' => 0],
                'upcomingSubscriptions' => [],
                'birthdaySurprise' => null,
            ]);
        }

        $space->load(['userOne', 'userTwo']);
        $partner = $space->getPartnerOf($user);

        // Wallets
        $wallets = Wallet::where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->with('user:id,name,nickname')
            ->get();

        $userWallets = $wallets->where('user_id', $user->id);
        $partnerWallets = $partner ? $wallets->where('user_id', $partner->id) : collect();
        $jointWallets = $wallets->where('type', 'joint');

        $savingsGoals = SavingsGoal::query()
            ->where('couple_space_id', $space->id)
            ->get(['created_by_user_id', 'scope', 'current_amount']);
        $savedInGoals = (float) $savingsGoals->sum('current_amount');
        $userPersonalSavings = (float) $savingsGoals
            ->where('scope', 'personal')
            ->where('created_by_user_id', $user->id)
            ->sum('current_amount');
        $partnerPersonalSavings = $partner
            ? (float) $savingsGoals
                ->where('scope', 'personal')
                ->where('created_by_user_id', $partner->id)
                ->sum('current_amount')
            : 0;
        $sharedSavings = (float) $savingsGoals->where('scope', 'shared')->sum('current_amount');
        $investments = Investment::query()
            ->where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->get(['user_id', 'scope', 'quantity', 'current_price']);
        $investmentValue = static fn (Investment $investment): float => (float) $investment->quantity * (float) $investment->current_price;
        $userInvestmentValue = $investments->where('user_id', $user->id)->sum($investmentValue);
        $partnerInvestmentValue = $partner
            ? $investments->where('user_id', $partner->id)->sum($investmentValue)
            : 0;
        $jointInvestmentValue = $investments->where('scope', 'shared')->sum($investmentValue);
        $totalInvestmentValue = $investments->sum($investmentValue);
        $totalNetWorth = (float) $wallets->sum('balance') + $savedInGoals + $totalInvestmentValue;
        $userNetWorth = (float) $userWallets->sum('balance') + $userPersonalSavings + $userInvestmentValue;
        $partnerNetWorth = (float) $partnerWallets->sum('balance') + $partnerPersonalSavings + $partnerInvestmentValue;
        $jointNetWorth = (float) $jointWallets->sum('balance') + $sharedSavings + $jointInvestmentValue;

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

        $monthTransactions = Transaction::where('couple_space_id', $space->id)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get();

        $monthlyTransferFees = (float) $monthTransactions->where('type', 'transfer')->sum('fee_amount');
        $monthlySpending = (float) $monthTransactions->where('type', 'expense')->sum('amount') + $monthlyTransferFees;
        $monthlyIncome = (float) $monthTransactions->where('type', 'income')->sum('amount');
        $todayTransactions = $monthTransactions
            ->filter(fn (Transaction $transaction): bool => $transaction->transaction_date->isToday());
        $todayExpenses = $monthTransactions
            ->where('type', 'expense')
            ->filter(fn (Transaction $transaction): bool => $transaction->transaction_date->isToday());
        $todayTransferFees = $todayTransactions->where('type', 'transfer')->sum('fee_amount');
        $dailySpending = (float) $todayExpenses->sum('amount') + (float) $todayTransferFees;
        $dailySpendingByUser = [
            'user' => (float) $todayExpenses->where('user_id', $user->id)->sum('amount')
                + (float) $todayTransactions->where('type', 'transfer')->where('user_id', $user->id)->sum('fee_amount'),
            'partner' => $partner
                ? (float) $todayExpenses->where('user_id', $partner->id)->sum('amount')
                    + (float) $todayTransactions->where('type', 'transfer')->where('user_id', $partner->id)->sum('fee_amount')
                : 0,
        ];
        $monthlyExpenses = $monthTransactions->where('type', 'expense');
        $monthlySpendingByUser = [
            'user' => (float) $monthlyExpenses->where('user_id', $user->id)->sum('amount')
                + (float) $monthTransactions->where('type', 'transfer')->where('user_id', $user->id)->sum('fee_amount'),
            'partner' => $partner
                ? (float) $monthlyExpenses->where('user_id', $partner->id)->sum('amount')
                    + (float) $monthTransactions->where('type', 'transfer')->where('user_id', $partner->id)->sum('fee_amount')
                : 0,
        ];
        $monthlyIncomes = $monthTransactions->where('type', 'income');
        $monthlyIncomeByUser = [
            'user' => (float) $monthlyIncomes->where('user_id', $user->id)->sum('amount'),
            'partner' => $partner ? (float) $monthlyIncomes->where('user_id', $partner->id)->sum('amount') : 0,
        ];

        // Scope Spending (Shared vs Personal)
        $sharedSpending = (float) $monthTransactions->where('type', 'expense')->where('scope', 'shared')->sum('amount');
        $personalSpending = (float) $monthTransactions->where('type', 'expense')->where('scope', 'personal')->sum('amount') + $monthlyTransferFees;
        $spendingByScope = [
            'shared' => $sharedSpending,
            'personal' => $personalSpending,
        ];

        $chartTransactions = Transaction::query()
            ->where('couple_space_id', $space->id)
            ->whereBetween('transaction_date', [
                $chartStartDate->copy()->startOfDay(),
                $chartEndDate->copy()->endOfDay(),
            ])
            ->get();
        $chartTransactionsByDate = $chartTransactions->groupBy(
            fn (Transaction $transaction): string => $transaction->transaction_date->toDateString()
        );

        // Cashflow Trend
        $dailyTrend = [];
        $indonesianDays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $indonesianMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        for ($date = $chartStartDate->copy(); $date->lte($chartEndDate); $date->addDay()) {
            $dayTransactions = $chartTransactionsByDate->get($date->toDateString(), collect());
            $dayExpense = (float) $dayTransactions->where('type', 'expense')->sum('amount')
                + (float) $dayTransactions->where('type', 'transfer')->sum('fee_amount');
            $dayIncome = (float) $dayTransactions->where('type', 'income')->sum('amount');

            $dailyTrend[] = [
                'date' => $date->format('d').' '.$indonesianMonths[$date->month - 1],
                'day' => $indonesianDays[$date->dayOfWeek],
                'expense' => $dayExpense,
                'income' => $dayIncome,
            ];
        }

        // Category Spending Breakdown (Selected Chart Period)
        $categories = Category::where(function ($q) use ($space) {
            $q->whereNull('couple_space_id')
                ->orWhere('couple_space_id', $space->id);
        })->get();

        $categorySpending = [];
        $expensesWithCategories = $chartTransactions->where('type', 'expense');
        $chartTransferFees = (float) $chartTransactions->where('type', 'transfer')->sum('fee_amount');
        $chartSpendingTotal = (float) $expensesWithCategories->sum('amount') + $chartTransferFees;
        $chartSpendingByScope = [
            'shared' => (float) $expensesWithCategories->where('scope', 'shared')->sum('amount'),
            'personal' => (float) $expensesWithCategories->where('scope', 'personal')->sum('amount') + $chartTransferFees,
        ];

        if ($chartSpendingTotal > 0) {
            $grouped = $expensesWithCategories->groupBy('category_id');
            foreach ($grouped as $catId => $txs) {
                $cat = $categories->firstWhere('id', $catId);
                $total = (float) $txs->sum('amount');
                $percentage = round(($total / $chartSpendingTotal) * 100);

                $categorySpending[] = [
                    'id' => $catId ?: 0,
                    'name' => $cat ? $cat->name : 'Tanpa Kategori',
                    'color' => $cat ? $cat->color : '#94A3B8',
                    'total' => $total,
                    'percentage' => $percentage,
                ];
            }

            usort($categorySpending, fn ($a, $b) => $b['total'] <=> $a['total']);
        }

        if ($chartTransferFees > 0) {
            $categorySpending[] = [
                'id' => -1,
                'name' => 'Biaya Admin Transfer',
                'color' => '#F59E0B',
                'total' => $chartTransferFees,
                'percentage' => round(($chartTransferFees / $chartSpendingTotal) * 100),
            ];
            usort($categorySpending, fn ($a, $b) => $b['total'] <=> $a['total']);
        }

        // Subscriptions due in next 7 days
        $upcomingSubscriptions = $space->subscriptions()
            ->where('is_active', true)
            ->whereBetween('next_billing_date', [Carbon::today(), Carbon::today()->addDays(7)])
            ->orderBy('next_billing_date', 'asc')
            ->get();

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
            'monthlyTransferFees' => $monthlyTransferFees,
            'dailySpending' => $dailySpending,
            'dailySpendingByUser' => $dailySpendingByUser,
            'monthlySpendingByUser' => $monthlySpendingByUser,
            'monthlyIncomeByUser' => $monthlyIncomeByUser,
            'categories' => $categories,
            'dailyTrend' => $dailyTrend,
            'categorySpending' => $categorySpending,
            'spendingByScope' => $spendingByScope,
            'chartPeriod' => $chartPeriod,
            'chartPeriodLabel' => $chartPeriodLabel,
            'chartSpendingTotal' => $chartSpendingTotal,
            'chartSpendingByScope' => $chartSpendingByScope,
            'upcomingSubscriptions' => $upcomingSubscriptions,
            'birthdaySurprise' => $this->birthdaySurpriseService->activeFor($user),
        ]);
    }

    /**
     * @return array{string, string, Carbon, Carbon}
     */
    private function resolveChartPeriod(Request $request): array
    {
        $chartPeriod = (string) $request->query('chart_period', '7d');

        if (! in_array($chartPeriod, self::CHART_PERIODS, true)) {
            $chartPeriod = '7d';
        }

        $chartEndDate = Carbon::today();
        [$chartPeriodLabel, $chartStartDate] = match ($chartPeriod) {
            '30d' => ['30 Hari', $chartEndDate->copy()->subDays(29)],
            'month' => ['Bulan Ini', $chartEndDate->copy()->startOfMonth()],
            default => ['7 Hari', $chartEndDate->copy()->subDays(6)],
        };

        return [$chartPeriod, $chartPeriodLabel, $chartStartDate, $chartEndDate];
    }
}
