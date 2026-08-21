<?php

namespace App\Services;

use App\Models\CoupleSpace;
use App\Models\Settlement;
use App\Models\TransactionSplit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    /**
     * Calculate unsettled balances between partners in a couple space.
     *
     * @return array{
     *     net_balance: float,
     *     debtor_id: int|null,
     *     creditor_id: int|null,
     *     debtor_name: string|null,
     *     creditor_name: string|null,
     *     amount_owed: float,
     *     user_one_balance: float,
     *     user_two_balance: float,
     *     unsettled_splits_count: int
     * }
     */
    public function getUnsettledBalance(CoupleSpace $space): array
    {
        $userOne = $space->userOne;
        $userTwo = $space->userTwo;

        if (! $userOne || ! $userTwo) {
            return [
                'net_balance' => 0.00,
                'debtor_id' => null,
                'creditor_id' => null,
                'debtor_name' => null,
                'creditor_name' => null,
                'amount_owed' => 0.00,
                'user_one_balance' => 0.00,
                'user_two_balance' => 0.00,
                'unsettled_splits_count' => 0,
            ];
        }

        // Get all unsettled splits for transactions in this space
        $splits = TransactionSplit::whereHas('transaction', function ($query) use ($space) {
            $query->where('couple_space_id', $space->id);
        })
            ->where('settled', false)
            ->get();

        $userOneNet = 0.00; // Positive = userOne is owed money (creditor), Negative = userOne owes money (debtor)

        foreach ($splits as $split) {
            if ($split->paid_by_user_id === $userOne->id) {
                // User 1 paid the bill. User 2 owes user_two_amount to User 1.
                $userOneNet += (float) $split->user_two_amount;
            } elseif ($split->paid_by_user_id === $userTwo->id) {
                // User 2 paid the bill. User 1 owes user_one_amount to User 2.
                $userOneNet -= (float) $split->user_one_amount;
            }
        }

        $userOneBalance = round($userOneNet, 2);
        $userTwoBalance = round(-$userOneNet, 2);

        $debtorId = null;
        $creditorId = null;
        $debtorName = null;
        $creditorName = null;
        $amountOwed = 0.00;

        if ($userOneNet > 0) {
            // User 2 owes User 1
            $debtorId = $userTwo->id;
            $debtorName = $userTwo->name;
            $creditorId = $userOne->id;
            $creditorName = $userOne->name;
            $amountOwed = $userOneNet;
        } elseif ($userOneNet < 0) {
            // User 1 owes User 2
            $debtorId = $userOne->id;
            $debtorName = $userOne->name;
            $creditorId = $userTwo->id;
            $creditorName = $userTwo->name;
            $amountOwed = abs($userOneNet);
        }

        return [
            'net_balance' => round($userOneNet, 2),
            'debtor_id' => $debtorId,
            'creditor_id' => $creditorId,
            'debtor_name' => $debtorName,
            'creditor_name' => $creditorName,
            'amount_owed' => round($amountOwed, 2),
            'user_one_balance' => $userOneBalance,
            'user_two_balance' => $userTwoBalance,
            'unsettled_splits_count' => $splits->count(),
        ];
    }

    /**
     * Record a settlement payment and mark unsettled splits as settled.
     *
     * @param  array<string, mixed>  $data
     */
    public function settle(CoupleSpace $space, User $fromUser, array $data): Settlement
    {
        return DB::transaction(function () use ($space, $fromUser, $data) {
            $toUserId = (int) $data['to_user_id'];
            $amount = (float) $data['amount'];
            $paymentMethod = $data['payment_method'];
            $notes = $data['notes'] ?? null;
            $settledAt = $data['settled_at'] ?? now();

            $settlement = Settlement::create([
                'couple_space_id' => $space->id,
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUserId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'settled_at' => $settledAt,
            ]);

            // Mark unsettled splits as settled
            TransactionSplit::whereHas('transaction', function ($query) use ($space) {
                $query->where('couple_space_id', $space->id);
            })
                ->where('settled', false)
                ->update(['settled' => true]);

            return $settlement->load(['fromUser', 'toUser']);
        });
    }
}
