<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
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

        $perPage = (int) $request->input('per_page', 20);
        $transactions = $query->paginate($perPage)->withQueryString();

        $wallets = Wallet::where('couple_space_id', $space->id)->get();
        $categories = Category::where(function ($q) use ($space) {
            $q->whereNull('couple_space_id')->orWhere('couple_space_id', $space->id);
        })->get();

        if ($request->wantsJson()) {
            return response()->json([
                'transactions' => $transactions,
                'filters' => $request->only(['search', 'scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']),
                'wallets' => $wallets,
                'categories' => $categories,
            ]);
        }

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'scope', 'type', 'category_id', 'wallet_id', 'start_date', 'end_date']),
            'wallets' => $wallets,
            'categories' => $categories,
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

        return redirect()->back()->with('success', 'Transaksi berhasil dicatat!');
    }

    /**
     * Update transaction and recalculate wallet balances.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $transaction->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized access to this transaction.');
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:150',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:expense,income,transfer',
            'scope' => 'required|in:personal,shared',
            'wallet_id' => 'required|exists:wallets,id',
            'to_wallet_id' => 'nullable|required_if:type,transfer|exists:wallets,id',
            'category_id' => 'nullable|exists:categories,id',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $updated = $this->transactionService->updateTransaction($transaction, $validated);

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

        if (! $space || $transaction->couple_space_id !== $space->id) {
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
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            abort(400, 'User is not part of an active couple space.');
        }

        $query = Transaction::where('couple_space_id', $space->id)
            ->with(['wallet', 'category', 'user'])
            ->orderBy('transaction_date', 'desc');

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

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('end_date'));
        }

        $transactions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-transaksi-'.date('Y-m-d').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions) {
            $output = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['ID', 'Tanggal', 'Judul Transaksi', 'Tipe', 'Cakupan', 'Kategori', 'Dompet', 'Nominal (Rp)', 'Dicatat Oleh', 'Catatan']);

            foreach ($transactions as $tx) {
                fputcsv($output, [
                    $tx->id,
                    $tx->transaction_date ? date('Y-m-d', strtotime($tx->transaction_date)) : '',
                    $tx->title ?: ($tx->category?->name ?? 'Transaksi'),
                    $tx->type,
                    $tx->scope === 'shared' ? 'Kencan/Bersama' : 'Pribadi',
                    $tx->category?->name ?? '-',
                    $tx->wallet?->name ?? '-',
                    $tx->amount,
                    $tx->user?->name ?? '-',
                    $tx->notes ?? '',
                ]);
            }

            fclose($output);
        }, 200, $headers);
    }
}
