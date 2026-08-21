<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wallet\StoreWalletRequest;
use App\Http\Requests\Wallet\UpdateWalletRequest;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    /**
     * List all wallets grouped by His Wallets, Her Wallets, and Joint Wallets with total net worth calculation.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $space = $user->currentCoupleSpace;

        if (! $space) {
            if ($request->wantsJson()) {
                return response()->json([
                    'his_wallets' => [],
                    'her_wallets' => [],
                    'joint_wallets' => [],
                    'user_wallets' => [],
                    'partner_wallets' => [],
                    'total_net_worth' => 0.00,
                    'user_net_worth' => 0.00,
                    'partner_net_worth' => 0.00,
                    'joint_net_worth' => 0.00,
                ]);
            }

            return Inertia::render('Wallets/Index', [
                'his_wallets' => [],
                'her_wallets' => [],
                'joint_wallets' => [],
                'total_net_worth' => 0.00,
                'user_net_worth' => 0.00,
                'partner_net_worth' => 0.00,
                'joint_net_worth' => 0.00,
            ]);
        }

        $space->load(['userOne', 'userTwo']);
        $partner = $space->getPartnerOf($user);

        $wallets = Wallet::where('couple_space_id', $space->id)
            ->where('is_active', true)
            ->get();

        $userOneWallets = $space->user_one_id
            ? $wallets->where('user_id', $space->user_one_id)->values()
            : collect();

        $userTwoWallets = $space->user_two_id
            ? $wallets->where('user_id', $space->user_two_id)->values()
            : collect();

        $jointWallets = $wallets->where('type', 'joint')->values();

        $userWallets = $wallets->where('user_id', $user->id)->values();
        $partnerWallets = $partner ? $wallets->where('user_id', $partner->id)->values() : collect();

        $userNetWorth = (float) $userWallets->sum('balance');
        $partnerNetWorth = (float) $partnerWallets->sum('balance');
        $jointNetWorth = (float) $jointWallets->sum('balance');
        $totalNetWorth = (float) $wallets->sum('balance');

        $data = [
            'his_wallets' => $userOneWallets,
            'her_wallets' => $userTwoWallets,
            'joint_wallets' => $jointWallets,
            'user_wallets' => $userWallets,
            'partner_wallets' => $partnerWallets,
            'total_net_worth' => round($totalNetWorth, 2),
            'user_net_worth' => round($userNetWorth, 2),
            'partner_net_worth' => round($partnerNetWorth, 2),
            'joint_net_worth' => round($jointNetWorth, 2),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Wallets/Index', $data);
    }

    /**
     * Create a new personal or joint wallet.
     */
    public function store(StoreWalletRequest $request): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        $type = $request->validated('type');
        $userId = ($type === 'personal') ? $user->id : null;

        $wallet = Wallet::create([
            'couple_space_id' => $space->id,
            'user_id' => $userId,
            'name' => $request->validated('name'),
            'type' => $type,
            'wallet_type' => $request->validated('wallet_type'),
            'account_number' => $request->validated('account_number'),
            'balance' => $request->validated('balance') ?? 0,
            'currency' => $request->validated('currency') ?? 'IDR',
            'color' => $request->validated('color') ?? '#6366F1',
            'icon' => $request->validated('icon') ?? 'wallet',
            'is_active' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Wallet created successfully.',
                'wallet' => $wallet,
            ], 201);
        }

        return redirect()->back()->with('success', 'Wallet created successfully.');
    }

    /**
     * Update wallet details and balance.
     */
    public function update(UpdateWalletRequest $request, Wallet $wallet): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        if ($wallet->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized access to this wallet.');
        }

        $data = array_filter($request->validated(), fn ($v) => $v !== null);
        $wallet->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Wallet updated successfully.',
                'wallet' => $wallet->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Wallet updated successfully.');
    }

    /**
     * Delete a wallet.
     */
    public function destroy(Request $request, Wallet $wallet): JsonResponse|RedirectResponse|Response
    {
        $user = $request->user();
        $space = $user->getOrEnsureCoupleSpace();

        if ($wallet->couple_space_id !== $space->id) {
            abort(403, 'Unauthorized access to this wallet.');
        }

        $wallet->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Wallet deleted successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Wallet deleted successfully.');
    }
}
