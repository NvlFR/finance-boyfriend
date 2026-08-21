<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            $data = ['budgets' => [], 'categories' => []];

            return $request->wantsJson() ? response()->json($data) : Inertia::render('Budgets/Index', $data);
        }

        $budgets = Budget::where('couple_space_id', $space->id)
            ->with(['category', 'user'])
            ->get();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Calculate actual spent per budget this month
        $budgetsWithSpent = $budgets->map(function ($budget) use ($space, $startOfMonth, $endOfMonth) {
            $query = Transaction::where('couple_space_id', $space->id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth]);

            if ($budget->category_id) {
                $query->where('category_id', $budget->category_id);
            }

            if ($budget->scope === 'personal' && $budget->user_id) {
                $query->where('user_id', $budget->user_id);
            }

            $spent = (float) $query->sum('amount');
            $limit = (float) $budget->limit_amount;
            $percentage = $limit > 0 ? min(100, round(($spent / $limit) * 100, 1)) : 0;

            $budget->spent_amount = $spent;
            $budget->percentage = $percentage;
            $budget->remaining_amount = max(0, $limit - $spent);
            $budget->is_overbudget = $spent > $limit;

            return $budget;
        });

        $categories = Category::where('type', 'expense')
            ->where(function ($q) use ($space) {
                $q->whereNull('couple_space_id')->orWhere('couple_space_id', $space->id);
            })->get();

        $data = [
            'budgets' => $budgetsWithSpent,
            'categories' => $categories,
        ];

        return $request->wantsJson() ? response()->json($data) : Inertia::render('Budgets/Index', $data);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'limit_amount' => 'required|numeric|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'scope' => 'nullable|in:shared,personal',
        ]);

        Budget::create([
            'couple_space_id' => $space->id,
            'user_id' => ($validated['scope'] ?? 'shared') === 'personal' ? $user->id : null,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'limit_amount' => $validated['limit_amount'],
            'period' => 'monthly',
            'scope' => $validated['scope'] ?? 'shared',
        ]);

        return redirect()->back()->with('success', 'Anggaran bulanan berhasil dibuat!');
    }

    public function update(Request $request, Budget $budget): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $budget->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'limit_amount' => 'required|numeric|min:1',
            'category_id' => 'nullable|exists:categories,id',
            'scope' => 'nullable|in:shared,personal',
        ]);

        $budget->update([
            'name' => $validated['name'],
            'limit_amount' => $validated['limit_amount'],
            'category_id' => $validated['category_id'] ?? null,
            'scope' => $validated['scope'] ?? 'shared',
            'user_id' => ($validated['scope'] ?? 'shared') === 'personal' ? $user->id : null,
        ]);

        return redirect()->back()->with('success', 'Anggaran berhasil diperbarui!');
    }

    public function destroy(Request $request, Budget $budget): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $budget->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $budget->delete();

        return redirect()->back()->with('success', 'Anggaran berhasil dihapus.');
    }
}
