<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * List transactions with filters (scope: personal/shared, category, date range, wallet).
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
                'transactions' => [],
                'filters' => $request->all(),
            ]);
        }

        $query = Transaction::where('couple_space_id', $space->id)
            ->with(['wallet', 'toWallet', 'category', 'split', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

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

        $perPage = (int) $request->input('per_page', 25);
        $transactions = $query->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json([
                'transactions' => $transactions,
                'filters' => $request->only(['scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']),
            ]);
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Record income/expense/transfer with automatic DB transaction to adjust wallet balances and create split if shared.
     */
    public function store(StoreTransactionRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            abort(400, 'User is not part of an active couple space.');
        }

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

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Delete transaction and rollback wallet balance.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $transaction->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized access to this transaction.');
        }

        $this->transactionService->deleteTransaction($transaction);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transaction deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }
}
