<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            $data = ['subscriptions' => [], 'total_monthly_cost' => 0];

            return $request->wantsJson() ? response()->json($data) : Inertia::render('Subscriptions/Index', $data);
        }

        $subs = Subscription::where('couple_space_id', $space->id)
            ->with(['paidByUser', 'wallet'])
            ->orderBy('next_billing_date', 'asc')
            ->get();

        $wallets = Wallet::where('couple_space_id', $space->id)->where('is_active', true)->get();

        $totalMonthlyCost = (float) $subs->where('is_active', true)->sum(function ($item) {
            return $item->billing_cycle === 'yearly' ? ($item->amount / 12) : $item->amount;
        });

        $data = [
            'subscriptions' => $subs,
            'wallets' => $wallets,
            'total_monthly_cost' => round($totalMonthlyCost, 2),
        ];

        return $request->wantsJson() ? response()->json($data) : Inertia::render('Subscriptions/Index', $data);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            abort(400, 'Not in a couple space.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'billing_cycle' => 'required|in:monthly,yearly',
            'next_billing_date' => 'required|date',
            'split_mode' => 'nullable|in:50_50,alternate,single',
            'paid_by_user_id' => 'nullable|exists:users,id',
            'wallet_id' => 'nullable|exists:wallets,id',
            'color' => 'nullable|string|max:20',
        ]);

        Subscription::create([
            'couple_space_id' => $space->id,
            'paid_by_user_id' => $validated['paid_by_user_id'] ?? $user->id,
            'wallet_id' => $validated['wallet_id'] ?? null,
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'billing_cycle' => $validated['billing_cycle'],
            'next_billing_date' => $validated['next_billing_date'],
            'split_mode' => $validated['split_mode'] ?? '50_50',
            'color' => $validated['color'] ?? '#6366F1',
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Langganan berhasil dicatat!');
    }

    public function update(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $subscription->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'billing_cycle' => 'required|in:monthly,yearly',
            'next_billing_date' => 'required|date',
            'split_mode' => 'nullable|in:50_50,alternate,single',
            'paid_by_user_id' => 'nullable|exists:users,id',
            'wallet_id' => 'nullable|exists:wallets,id',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $subscription->update($validated);

        return redirect()->back()->with('success', 'Langganan berhasil diperbarui!');
    }

    public function destroy(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $subscription->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $subscription->delete();

        return redirect()->back()->with('success', 'Langganan berhasil dihapus.');
    }
}
