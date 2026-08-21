<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            $data = ['wishlists' => []];

            return $request->wantsJson() ? response()->json($data) : Inertia::render('Wishlists/Index', $data);
        }

        $wishlists = Wishlist::where('couple_space_id', $space->id)
            ->with(['user', 'targetUser'])
            ->orderBy('is_bought', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        // For secret surprises, if the target is current user, we hide/blur title and price unless bought!
        $formattedWishlists = $wishlists->map(function ($item) use ($user) {
            if ($item->is_secret_surprise && $item->target_user_id === $user->id && ! $item->is_bought) {
                $item->title = '🎁 Secret Surprise Gift for You!';
                $item->estimated_price = '0.00';
                $item->notes = 'Disembunyikan oleh pasanganmu sampai harinya tiba ✨';
                $item->url = null;
            }

            return $item;
        });

        $data = [
            'wishlists' => $formattedWishlists,
        ];

        return $request->wantsJson() ? response()->json($data) : Inertia::render('Wishlists/Index', $data);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            abort(400, 'Not in a couple space.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'estimated_price' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:low,medium,high',
            'url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:500',
            'is_secret_surprise' => 'nullable|boolean',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        $space->load(['userOne', 'userTwo']);
        $partner = $space->getPartnerOf($user);

        Wishlist::create([
            'couple_space_id' => $space->id,
            'user_id' => $user->id,
            'title' => $validated['title'],
            'estimated_price' => $validated['estimated_price'] ?? 0,
            'priority' => $validated['priority'] ?? 'medium',
            'url' => $validated['url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_secret_surprise' => $validated['is_secret_surprise'] ?? false,
            'target_user_id' => ($validated['is_secret_surprise'] ?? false) ? ($partner?->id) : null,
            'is_bought' => false,
        ]);

        return redirect()->back()->with('success', 'Wishlist berhasil ditambahkan!');
    }

    public function toggleBought(Request $request, Wishlist $wishlist): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $wishlist->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $wishlist->update([
            'is_bought' => ! $wishlist->is_bought,
        ]);

        return redirect()->back()->with('success', 'Status wishlist diperbarui!');
    }

    public function update(Request $request, Wishlist $wishlist): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $wishlist->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'estimated_price' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:low,medium,high',
            'url' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:500',
            'is_secret_surprise' => 'nullable|boolean',
        ]);

        $wishlist->update($validated);

        return redirect()->back()->with('success', 'Wishlist berhasil diperbarui!');
    }

    public function destroy(Request $request, Wishlist $wishlist): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space || $wishlist->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized.');
        }

        $wishlist->delete();

        return redirect()->back()->with('success', 'Wishlist berhasil dihapus.');
    }
}
