<?php

namespace App\Http\Controllers;

use App\Models\SavingsContribution;
use App\Models\SavingsGoal;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $wallets = Wallet::where('couple_space_id', $space->id)->where('is_active', true)->get();

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
        ]);

        $goal = SavingsGoal::create([
            'couple_space_id' => $space->id,
            'created_by_user_id' => $user->id,
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

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'wallet_id' => 'nullable|exists:wallets,id',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($user, $savingsGoal, $validated) {
            $amount = (float) $validated['amount'];

            // Deduct wallet balance if specified
            if (! empty($validated['wallet_id'])) {
                $wallet = Wallet::findOrFail($validated['wallet_id']);
                $wallet->decrement('balance', $amount);
            }

            // Create contribution log
            SavingsContribution::create([
                'savings_goal_id' => $savingsGoal->id,
                'user_id' => $user->id,
                'wallet_id' => $validated['wallet_id'] ?? null,
                'amount' => $amount,
                'notes' => $validated['notes'] ?? null,
                'contributed_at' => now(),
            ]);

            // Update goal current amount
            $savingsGoal->increment('current_amount', $amount);

            if ((float) $savingsGoal->current_amount >= (float) $savingsGoal->target_amount) {
                $savingsGoal->update(['status' => 'achieved']);
            }
        });

        return redirect()->back()->with('success', 'Setoran tabungan berhasil dicatat!');
    }

    public function update(Request $request, SavingsGoal $savingsGoal): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $savingsGoal->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'target_date' => 'nullable|date',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

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

        $savingsGoal->delete();

        return redirect()->back()->with('success', 'Target tabungan berhasil dihapus.');
    }
}
