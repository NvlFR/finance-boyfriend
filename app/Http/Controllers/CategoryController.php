<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
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
    public function store(StoreCategoryRequest $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            abort(400, 'User is not part of an active couple space.');
        }

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

        return redirect()->back()->with('success', 'Category created successfully.');
    }
}
