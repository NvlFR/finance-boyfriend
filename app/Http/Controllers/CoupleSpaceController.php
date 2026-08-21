<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoupleSpace\JoinCoupleSpaceRequest;
use App\Http\Requests\CoupleSpace\StoreCoupleSpaceRequest;
use App\Http\Requests\CoupleSpace\UpdateCoupleSpaceRequest;
use App\Models\CoupleSpace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoupleSpaceController extends Controller
{
    /**
     * Get current user's couple space details.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace()
            ->with(['userOne', 'userTwo'])
            ->first();

        $stats = [
            'joint_net_worth' => $space ? (float) $space->wallets()->where('type', 'joint')->sum('balance') : 0,
            'active_goals_count' => $space ? $space->savingsGoals()->count() : 0,
            'active_subscriptions_count' => $space ? $space->subscriptions()->where('is_active', true)->count() : 0,
            'wishlists_count' => $space ? $space->wishlists()->count() : 0,
            'transactions_count' => $space ? $space->transactions()->count() : 0,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'couple_space' => $space,
                'partner' => $space ? $space->getPartnerOf($user) : null,
                'stats' => $stats,
            ]);
        }

        return Inertia::render('CoupleSpace/Index', [
            'coupleSpace' => $space,
            'partner' => $space ? $space->getPartnerOf($user) : null,
            'stats' => $stats,
        ]);
    }

    /**
     * Show specific couple space or current space.
     */
    public function show(Request $request, ?CoupleSpace $coupleSpace = null): JsonResponse|Response
    {
        $user = $request->user();
        $space = $coupleSpace && $coupleSpace->exists ? $coupleSpace : $user->currentCoupleSpace;

        if (! $space) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No active couple space found.'], 404);
            }

            return redirect()->route('dashboard');
        }

        if ($space->user_one_id !== $user->id && $space->user_two_id !== $user->id) {
            abort(403, 'Unauthorized access to this couple space.');
        }

        $space->load(['userOne', 'userTwo']);

        if ($request->wantsJson()) {
            return response()->json([
                'couple_space' => $space,
                'partner' => $space->getPartnerOf($user),
            ]);
        }

        return Inertia::render('CoupleSpace/Show', [
            'coupleSpace' => $space,
            'partner' => $space->getPartnerOf($user),
        ]);
    }

    /**
     * Create a new couple space with generated invite_code.
     */
    public function store(StoreCoupleSpaceRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();

        $space = CoupleSpace::create([
            'name' => $request->validated('name'),
            'invite_code' => CoupleSpace::generateInviteCode(),
            'user_one_id' => $user->id,
            'user_two_id' => null,
            'status' => 'pending',
            'anniversary_date' => $request->validated('anniversary_date'),
        ]);

        $user->update(['current_couple_space_id' => $space->id]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Couple space created successfully.',
                'couple_space' => $space->load(['userOne', 'userTwo']),
            ], 201);
        }

        return redirect()->back()->with('success', 'Couple space created successfully.');
    }

    /**
     * Join an existing couple space via invite_code.
     */
    public function join(JoinCoupleSpaceRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $inviteCode = $request->validated('invite_code');

        $space = CoupleSpace::where('invite_code', $inviteCode)->firstOrFail();

        if ($space->user_one_id === $user->id) {
            return response()->json([
                'message' => 'You cannot join your own couple space.',
            ], 422);
        }

        if ($space->user_two_id !== null && $space->user_two_id !== $user->id) {
            return response()->json([
                'message' => 'This couple space is already full.',
            ], 422);
        }

        $space->update([
            'user_two_id' => $user->id,
            'status' => 'active',
        ]);

        $user->update(['current_couple_space_id' => $space->id]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Joined couple space successfully.',
                'couple_space' => $space->load(['userOne', 'userTwo']),
            ]);
        }

        return redirect()->back()->with('success', 'Joined couple space successfully.');
    }

    /**
     * Update couple space settings.
     */
    public function update(UpdateCoupleSpaceRequest $request, CoupleSpace $coupleSpace): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();

        if ($coupleSpace->user_one_id !== $user->id && $coupleSpace->user_two_id !== $user->id) {
            abort(403, 'Unauthorized access to this couple space.');
        }

        $coupleSpace->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Couple space updated successfully.',
                'couple_space' => $coupleSpace->fresh(['userOne', 'userTwo']),
            ]);
        }

        return redirect()->back()->with('success', 'Couple space updated successfully.');
    }
}
