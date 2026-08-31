<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settlement\StoreSettlementRequest;
use App\Models\Settlement;
use App\Models\TransactionSplit;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettlementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    /**
     * Get current unsettled balance between partners (who owes whom how much) and settlement history.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            $emptyData = [
                'unsettled' => [
                    'net_balance' => 0.00,
                    'debtor_id' => null,
                    'creditor_id' => null,
                    'debtor_name' => null,
                    'creditor_name' => null,
                    'amount_owed' => 0.00,
                    'user_one_balance' => 0.00,
                    'user_two_balance' => 0.00,
                    'unsettled_splits_count' => 0,
                ],
                'history' => [
                    'data' => [],
                    'links' => [],
                ],
                'unsettledItems' => [],
            ];

            if ($request->wantsJson()) {
                return response()->json($emptyData);
            }

            return Inertia::render('Settlements/Index', $emptyData);
        }

        $space->load(['userOne', 'userTwo']);
        $unsettled = $this->settlementService->getUnsettledBalance($space);
        $unsettledItems = TransactionSplit::query()
            ->whereHas('transaction', fn ($query) => $query->where('couple_space_id', $space->id))
            ->where('settled', false)
            ->with([
                'paidByUser:id,name,nickname',
                'transaction:id,user_id,category_id,title,amount,transaction_date',
                'transaction.category:id,name,color',
            ])
            ->latest('id')
            ->get()
            ->map(fn (TransactionSplit $split): array => [
                'id' => $split->id,
                'title' => $split->transaction->title ?: ($split->transaction->category_id
                    ? $split->transaction->category->name
                    : 'Transaksi bersama'),
                'amount' => $split->transaction->amount,
                'transaction_date' => $split->transaction->transaction_date,
                'paid_by_name' => $split->paidByUser?->nickname ?: $split->paidByUser?->name,
                'user_one_amount' => $split->user_one_amount,
                'user_two_amount' => $split->user_two_amount,
                'split_type' => $split->split_type,
            ]);

        $history = Settlement::where('couple_space_id', $space->id)
            ->with(['fromUser', 'toUser'])
            ->orderBy('settled_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))));

        $data = [
            'unsettled' => $unsettled,
            'unsettledItems' => $unsettledItems,
            'history' => $history,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Settlements/Index', $data);
    }

    /**
     * Settle up debt between partners.
     */
    public function store(StoreSettlementRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $settlement = $this->settlementService->settle(
            $space,
            $user,
            $request->validated()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Settlement recorded successfully.',
                'settlement' => $settlement,
            ], 201);
        }

        return redirect()->back()->with('success', 'Settlement recorded successfully.');
    }
}
