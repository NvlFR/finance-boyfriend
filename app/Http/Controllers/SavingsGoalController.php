<?php

namespace App\Http\Controllers;

use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            $data = ['goals' => [], 'total_saved' => 0, 'total_target' => 0];

            return $request->wantsJson() ? response()->json($data) : Inertia::render('Goals/Index', $data);
        }

        $goals = SavingsGoal::where('couple_space_id', $space->id)
            ->with(['contributions.user', 'createdByUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        $wallets = Wallet::where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)->orWhere('type', 'joint');
            })
            ->with('user:id,name,nickname')
            ->get();

        $totalSaved = (float) $goals->sum('current_amount');
        $totalTarget = (float) $goals->sum('target_amount');

        $data = [
            'goals' => $goals,
            'wallets' => $wallets,
            'total_saved' => $totalSaved,
            'total_target' => $totalTarget,
        ];

        return $request->wantsJson() ? response()->json($data) : Inertia::render('Goals/Index', $data);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'target_date' => 'nullable|date',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'scope' => ['sometimes', Rule::in(['personal', 'shared'])],
        ]);

        $goal = SavingsGoal::create([
            'couple_space_id' => $space->id,
            'created_by_user_id' => $user->id,
            'scope' => $validated['scope'] ?? 'personal',
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'current_amount' => 0,
            'target_date' => $validated['target_date'] ?? null,
            'icon' => $validated['icon'] ?? 'target',
            'color' => $validated['color'] ?? '#6366F1',
            'status' => 'in_progress',
        ]);

        return redirect()->back()->with('success', 'Target tabungan berhasil dibuat!');
    }

    public function contribute(Request $request, SavingsGoal $savingsGoal): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $savingsGoal->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('update', $savingsGoal);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'wallet_id' => [
                'nullable',
                Rule::exists('wallets', 'id')->where(fn ($query) => $query
                    ->where('couple_space_id', $space->id)
                    ->where('is_active', true)
                    ->where(function ($walletQuery) use ($savingsGoal, $user): void {
                        if ($savingsGoal->scope === 'personal') {
                            $walletQuery->where('type', 'personal')->where('user_id', $user->id);

                            return;
                        }

                        $walletQuery->where(fn ($allowedWalletQuery) => $allowedWalletQuery
                            ->where('user_id', $user->id)
                            ->orWhere('type', 'joint'));
                    })),
            ],
            'notes' => 'nullable|string|max:255',
            'client_reference' => 'nullable|string|max:64',
        ]);

        $clientReference = $validated['client_reference'] ?? null;

        if ($clientReference && SavingsContribution::query()
            ->where('user_id', $user->id)
            ->where('client_reference', $clientReference)
            ->exists()) {
            return redirect()->back()->with('success', 'Setoran tabungan sudah tercatat.');
        }

        try {
            DB::transaction(function () use ($user, $savingsGoal, $validated, $space, $clientReference) {
                $amount = (float) $validated['amount'];
                $lockedGoal = SavingsGoal::query()
                    ->whereKey($savingsGoal->id)
                    ->where('couple_space_id', $space->id)
                    ->where(function ($query) use ($user): void {
                        $query->where('scope', 'shared')->orWhere('created_by_user_id', $user->id);
                    })
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! empty($validated['wallet_id'])) {
                    $wallet = Wallet::query()
                        ->where('couple_space_id', $space->id)
                        ->where(function ($query) use ($lockedGoal, $user): void {
                            if ($lockedGoal->scope === 'personal') {
                                $query->where('type', 'personal')->where('user_id', $user->id);

                                return;
                            }

                            $query->where(fn ($allowedWalletQuery) => $allowedWalletQuery
                                ->where('user_id', $user->id)
                                ->orWhere('type', 'joint'));
                        })
                        ->whereKey($validated['wallet_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((float) $wallet->balance < $amount) {
                        throw ValidationException::withMessages([
                            'amount' => 'Saldo dompet tidak mencukupi untuk setoran ini.',
                        ]);
                    }

                    $wallet->decrement('balance', $amount);
                }

                SavingsContribution::create([
                    'savings_goal_id' => $lockedGoal->id,
                    'user_id' => $user->id,
                    'wallet_id' => $validated['wallet_id'] ?? null,
                    'amount' => $amount,
                    'notes' => $validated['notes'] ?? null,
                    'client_reference' => $clientReference,
                    'contributed_at' => now(),
                ]);

                $lockedGoal->increment('current_amount', $amount);
                $lockedGoal->refresh();

                if ((float) $lockedGoal->current_amount >= (float) $lockedGoal->target_amount) {
                    $lockedGoal->update(['status' => 'achieved']);
                }
            });
        } catch (QueryException $exception) {
            if ($clientReference && $exception->getCode() === '23000') {
                $existingContribution = SavingsContribution::query()
                    ->where('user_id', $user->id)
                    ->where('client_reference', $clientReference)
                    ->first();

                if ($existingContribution) {
                    return redirect()->back()->with('success', 'Setoran tabungan sudah tercatat.');
                }
            }

            throw $exception;
        }

        return redirect()->back()->with('success', 'Setoran tabungan berhasil dicatat!');
    }

    public function update(Request $request, SavingsGoal $savingsGoal): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $savingsGoal->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('update', $savingsGoal);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'target_date' => 'nullable|date',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $validated['status'] = (float) $savingsGoal->current_amount >= (float) $validated['target_amount']
            ? 'achieved'
            : 'in_progress';
        $savingsGoal->update($validated);

        return redirect()->back()->with('success', 'Target tabungan berhasil diperbarui!');
    }

    public function destroy(Request $request, SavingsGoal $savingsGoal): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $savingsGoal->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        Gate::authorize('delete', $savingsGoal);

        DB::transaction(function () use ($savingsGoal): void {
            $lockedGoal = SavingsGoal::query()->whereKey($savingsGoal->id)->lockForUpdate()->firstOrFail();
            $refunds = $lockedGoal->contributions()
                ->whereNotNull('wallet_id')
                ->selectRaw('wallet_id, SUM(amount) as total_amount')
                ->groupBy('wallet_id')
                ->get();

            foreach ($refunds as $refund) {
                Wallet::query()
                    ->where('couple_space_id', $lockedGoal->couple_space_id)
                    ->whereKey($refund->wallet_id)
                    ->lockForUpdate()
                    ->first()?->increment('balance', (float) $refund->total_amount);
            }

            $lockedGoal->delete();
        });

        return redirect()->back()->with('success', 'Target tabungan berhasil dihapus.');
    }
}
