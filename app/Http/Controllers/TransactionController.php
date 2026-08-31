<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\SettlementService;
use App\Services\TransactionReportService;
use App\Services\TransactionService;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected TransactionReportService $transactionReportService,
        protected SettlementService $settlementService,
    ) {}

    /**
     * List transactions with filters (scope: personal/shared, category, date range, wallet, search).
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            if ($request->wantsJson()) {
                return response()->json([
                    'transactions' => [],
                    'filters' => $request->all(),
                ]);
            }

            return Inertia::render('Transactions/Index', [
                'transactions' => [
                    'data' => [],
                    'links' => [],
                    'total' => 0,
                ],
                'filters' => $request->all(),
                'wallets' => [],
                'categories' => [],
                'savingsMovements' => [],
            ]);
        }

        $query = Transaction::where('couple_space_id', $space->id)
            ->with(['wallet', 'toWallet', 'category', 'split', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('scope')) {
            $query->where('scope', $request->input('scope'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('wallet_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('wallet_id', $request->input('wallet_id'))
                    ->orWhere('to_wallet_id', $request->input('wallet_id'));
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('end_date'));
        }

        $perPage = min(100, max(1, $request->integer('per_page', 20)));
        $transactions = $query->paginate($perPage)->withQueryString();

        $wallets = Wallet::where('couple_space_id', $space->id)
            ->with('user:id,name,nickname')
            ->get();
        $categories = Category::where(function ($q) use ($space) {
            $q->whereNull('couple_space_id')->orWhere('couple_space_id', $space->id);
        })->get();
        $savingsMovements = SavingsContribution::query()
            ->whereHas('goal', fn ($query) => $query->where('couple_space_id', $space->id))
            ->with(['goal:id,name', 'wallet.user:id,name,nickname', 'user:id,name,nickname'])
            ->latest('contributed_at')
            ->limit(20)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'transactions' => $transactions,
                'filters' => $request->only(['search', 'scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']),
                'wallets' => $wallets,
                'categories' => $categories,
                'savingsMovements' => $savingsMovements,
            ]);
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']),
            'wallets' => $wallets,
            'categories' => $categories,
            'savingsMovements' => $savingsMovements,
        ]);
    }

    /**
     * Record income/expense/transfer with automatic DB transaction to adjust wallet balances and create split if shared.
     */
    public function store(StoreTransactionRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $transaction = $this->transactionService->createTransaction(
            $user,
            $space,
            $request->validated()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transaction recorded successfully.',
                'transaction' => $transaction,
            ], 201);
        }

        return redirect()->back()->with('success', 'Transaksi berhasil dicatat!');
    }

    /**
     * Update transaction and recalculate wallet balances.
     */
    public function update(StoreTransactionRequest $request, Transaction $transaction): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $transaction->couple_space_id !== $space->id || $transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this transaction.');
        }

        $updated = $this->transactionService->updateTransaction($transaction, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transaction updated successfully.',
                'transaction' => $updated,
            ]);
        }

        return redirect()->back()->with('success', 'Transaksi berhasil diperbarui!');
    }

    /**
     * Delete transaction and rollback wallet balance.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $transaction->couple_space_id !== $space->id || $transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this transaction.');
        }

        $this->transactionService->deleteTransaction($transaction);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transaction deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Transaksi berhasil dihapus!');
    }

    /**
     * Export filtered transactions to CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $space = $request->user()->getOrEnsureCoupleSpace();
        $transactions = $this->filteredExportTransactions($request, $space->id);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-transaksi-'.date('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions) {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new RuntimeException('Tidak dapat membuka output laporan CSV.');
            }

            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['ID', 'Tanggal', 'Judul Transaksi', 'Tipe', 'Cakupan', 'Kategori', 'Dompet Asal', 'Dompet Tujuan', 'Nominal (Rp)', 'Biaya Admin (Rp)', 'Total Potong Dompet Asal (Rp)', 'Dicatat Oleh', 'Catatan']);

            foreach ($transactions as $tx) {
                $sourceDebit = $tx->type === 'transfer'
                    ? BigDecimal::of($tx->amount)->plus($tx->fee_amount)->toScale(2)->__toString()
                    : $tx->amount;

                fputcsv($output, [
                    $tx->id,
                    $tx->transaction_date->format('Y-m-d H:i'),
                    $tx->title ?: ($tx->category_id ? $tx->category->name : 'Transaksi'),
                    $tx->type,
                    $tx->scope === 'shared' ? 'Bersama' : 'Pribadi',
                    $tx->category_id ? $tx->category->name : '-',
                    $tx->wallet->name,
                    $tx->to_wallet_id ? $tx->toWallet->name : '-',
                    $tx->amount,
                    $tx->fee_amount,
                    $sourceDebit,
                    $tx->user->name,
                    $tx->notes ?? '',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }

    public function exportExcel(Request $request): HttpResponse
    {
        $space = $request->user()->getOrEnsureCoupleSpace();
        $transactions = $this->filteredExportTransactions($request, $space->id);

        return response($this->transactionReportService->excel($transactions), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="laporan-transaksi-'.now()->format('Y-m-d').'.xlsx"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function exportPdf(Request $request): View
    {
        $space = $request->user()->getOrEnsureCoupleSpace();
        $space->load(['userOne', 'userTwo']);
        $transactions = $this->filteredExportTransactions($request, $space->id);
        $budgetTransactions = Transaction::query()
            ->where('couple_space_id', $space->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();
        $wallets = Wallet::query()
            ->where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->with('user:id,name,nickname')
            ->get();
        $budgets = Budget::query()
            ->where('couple_space_id', $space->id)
            ->with(['category:id,name', 'user:id,name,nickname'])
            ->get();
        $savingsGoals = SavingsGoal::query()
            ->where('couple_space_id', $space->id)
            ->orderByDesc('current_amount')
            ->get();
        $subscriptions = Subscription::query()
            ->where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->with(['paidByUser:id,name,nickname', 'wallet:id,name'])
            ->orderBy('next_billing_date')
            ->get();
        $filters = $request->only(['search', 'scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']);

        return view('reports.financial', $this->transactionReportService->financialReport(
            $space,
            $transactions,
            $budgetTransactions,
            $wallets,
            $budgets,
            $savingsGoals,
            $subscriptions,
            $this->settlementService->getUnsettledBalance($space),
            $filters,
        ));
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function filteredExportTransactions(Request $request, int $spaceId): Collection
    {
        $query = Transaction::query()
            ->where('couple_space_id', $spaceId)
            ->with(['wallet', 'toWallet', 'category', 'user'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $this->applyFilters($query, $request);

        return $query->get();
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $searchQuery) => $searchQuery
                ->where('title', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%"));
        }

        foreach (['scope', 'type', 'category_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('wallet_id')) {
            $walletId = $request->integer('wallet_id');
            $query->where(fn (Builder $walletQuery) => $walletQuery
                ->where('wallet_id', $walletId)
                ->orWhere('to_wallet_id', $walletId));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->date('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->date('end_date'));
        }
    }
}
