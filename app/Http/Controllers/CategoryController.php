<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * List default and custom categories for the couple space.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        $categories = Category::where('is_default', true)
            ->when($space, function ($query) use ($space) {
                $query->orWhere('couple_space_id', $space->id);
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $incomeCategories = $categories->where('type', 'income')->values();
        $expenseCategories = $categories->where('type', 'expense')->values();

        $data = [
            'categories' => $categories,
            'income_categories' => $incomeCategories,
            'expense_categories' => $expenseCategories,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Categories/Index', $data);
    }

    /**
     * Create a custom category for the user's couple space.
     */
    public function store(StoreCategoryRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $category = Category::create([
            'couple_space_id' => $space->id,
            'name' => $request->validated('name'),
            'type' => $request->validated('type'),
            'icon' => $request->validated('icon', 'tag'),
            'color' => $request->validated('color', '#6366F1'),
            'is_default' => false,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Category created successfully.',
                'category' => $category,
            ], 201);
        }

        return redirect()->back()->with('success', 'Kategori baru berhasil dibuat!');
    }

    /**
     * Update custom category.
     */
    public function update(Request $request, Category $category): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $category->couple_space_id !== $space->id || $category->is_default) {
            abort(403, 'Kategori sistem default tidak dapat diubah.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense,both',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $category->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Category updated successfully.',
                'category' => $category,
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Delete custom category.
     */
    public function destroy(Request $request, Category $category): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $category->couple_space_id !== $space->id || $category->is_default) {
            abort(403, 'Kategori sistem default tidak dapat dihapus.');
        }

        $category->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Category deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
    }
}
