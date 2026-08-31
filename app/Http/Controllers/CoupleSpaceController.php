<?php

namespace App\Http\Controllers;

use App\Http\Requests\CoupleSpace\JoinCoupleSpaceRequest;
use App\Http\Requests\CoupleSpace\StoreCoupleSpaceRequest;
use App\Http\Requests\CoupleSpace\UpdateCoupleSpaceRequest;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CoupleSpace;
use App\Models\SavingsGoal;
use App\Models\Settlement;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\Wallet;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'canManageBirthdaySurprise' => $space?->canManageBirthdaySurprise($user) ?? false,
        ]);
    }

    /**
     * Show specific couple space or current space.
     */
    public function show(Request $request, ?CoupleSpace $coupleSpace = null): JsonResponse|RedirectResponse|Response
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

        $space = DB::transaction(function () use ($request, $user): CoupleSpace {
            $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->current_couple_space_id) {
                throw ValidationException::withMessages([
                    'name' => 'Kamu sudah memiliki ruang pasangan aktif.',
                ]);
            }

            $space = CoupleSpace::create([
                'name' => $request->validated('name'),
                'invite_code' => CoupleSpace::generateInviteCode(),
                'user_one_id' => $lockedUser->id,
                'user_two_id' => null,
                'status' => 'pending',
                'anniversary_date' => $request->validated('anniversary_date'),
            ]);

            $lockedUser->update(['current_couple_space_id' => $space->id]);

            return $space;
        }, attempts: 3);

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

        $space = DB::transaction(function () use ($inviteCode, $user): CoupleSpace {
            $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $space = CoupleSpace::query()
                ->where('invite_code', $inviteCode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($space->user_one_id === $lockedUser->id) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Kamu tidak dapat bergabung ke ruang milikmu sendiri.',
                ]);
            }

            if ($space->user_two_id !== null && $space->user_two_id !== $user->id) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Ruang pasangan ini sudah penuh.',
                ]);
            }

            $oldSpace = $lockedUser->current_couple_space_id
                ? CoupleSpace::query()->lockForUpdate()->find($lockedUser->current_couple_space_id)
                : null;

            $personalSpace = null;

            if ($oldSpace && $oldSpace->id !== $space->id) {
                if ($oldSpace->status !== 'pending' || $oldSpace->user_one_id !== $lockedUser->id || $oldSpace->user_two_id !== null) {
                    throw ValidationException::withMessages([
                        'invite_code' => 'Keluar dari ruang pasangan aktif sebelum bergabung ke ruang lain.',
                    ]);
                }

                $this->mergePersonalSpace($oldSpace, $space);
                $personalSpace = $oldSpace;
            }

            $space->update([
                'user_two_id' => $lockedUser->id,
                'status' => 'active',
            ]);
            $lockedUser->update(['current_couple_space_id' => $space->id]);
            $personalSpace?->delete();

            return $space;
        }, attempts: 3);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Joined couple space successfully.',
                'couple_space' => $space->load(['userOne', 'userTwo']),
            ]);
        }

        return redirect()->back()->with('success', 'Joined couple space successfully.');
    }

    private function mergePersonalSpace(CoupleSpace $source, CoupleSpace $destination): void
    {
        Category::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Wallet::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Transaction::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Budget::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Subscription::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        SavingsGoal::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Wishlist::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Trip::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
        Settlement::where('couple_space_id', $source->id)->update(['couple_space_id' => $destination->id]);
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
