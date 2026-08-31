<?php

namespace App\Services;

use App\Models\CoupleSpace;
use App\Models\Settlement;
use App\Models\TransactionSplit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

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

        return $this->calculateBalance($space, $splits);
    }

    /**
     * @param  Collection<int, TransactionSplit>  $splits
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
    private function calculateBalance(CoupleSpace $space, Collection $splits): array
    {
        $userOne = $space->userOne;
        $userTwo = $space->userTwo;
        $userOneNet = 0.00;

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
            $debtorId = $userTwo->id;
            $debtorName = $userTwo->name;
            $creditorId = $userOne->id;
            $creditorName = $userOne->name;
            $amountOwed = $userOneNet;
        } elseif ($userOneNet < 0) {
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
        $clientReference = $data['client_reference'] ?? null;

        if ($clientReference) {
            $existingSettlement = Settlement::query()
                ->where('couple_space_id', $space->id)
                ->where('from_user_id', $fromUser->id)
                ->where('client_reference', $clientReference)
                ->first();

            if ($existingSettlement) {
                return $existingSettlement->load(['fromUser', 'toUser']);
            }
        }

        return DB::transaction(function () use ($space, $fromUser, $data) {
            CoupleSpace::query()->whereKey($space->id)->lockForUpdate()->firstOrFail();

            if (! empty($data['client_reference'])) {
                $existingSettlement = Settlement::query()
                    ->where('couple_space_id', $space->id)
                    ->where('from_user_id', $fromUser->id)
                    ->where('client_reference', $data['client_reference'])
                    ->first();

                if ($existingSettlement) {
                    return $existingSettlement->load(['fromUser', 'toUser']);
                }
            }

            $space->loadMissing(['userOne', 'userTwo']);
            $splits = TransactionSplit::whereHas('transaction', function ($query) use ($space) {
                $query->where('couple_space_id', $space->id);
            })
                ->where('settled', false)
                ->lockForUpdate()
                ->get();
            $balance = $this->calculateBalance($space, $splits);
            $toUserId = (int) $data['to_user_id'];
            $amount = (float) $data['amount'];
            $paymentMethod = $data['payment_method'];
            $notes = $data['notes'] ?? null;
            $settledAt = $data['settled_at'] ?? now();
            $paymentMode = $data['payment_mode'] ?? 'external';

            if ($balance['debtor_id'] !== $fromUser->id) {
                throw ValidationException::withMessages([
                    'amount' => 'Kamu tidak memiliki utang yang perlu dilunasi saat ini.',
                ]);
            }

            if ($balance['creditor_id'] !== $toUserId) {
                throw ValidationException::withMessages([
                    'to_user_id' => 'Penerima pelunasan harus pasangan yang saat ini kamu utangi.',
                ]);
            }

            if (abs((float) $balance['amount_owed'] - $amount) > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pelunasan harus sama dengan total utang saat ini.',
                ]);
            }

            $transferTransaction = null;
            if ($paymentMode === 'wallet_transfer') {
                $transferTransaction = $this->transactionService->createTransaction($fromUser, $space, [
                    'wallet_id' => $data['source_wallet_id'],
                    'to_wallet_id' => $data['destination_wallet_id'],
                    'type' => 'transfer',
                    'scope' => 'personal',
                    'amount' => $amount,
                    'transaction_date' => $settledAt,
                    'title' => 'Pelunasan talangan ke '.$balance['creditor_name'],
                    'notes' => $notes,
                    'client_reference' => isset($data['client_reference']) ? 'settlement-'.$data['client_reference'] : null,
                ]);
            }

            $settlement = Settlement::create([
                'couple_space_id' => $space->id,
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUserId,
                'transaction_id' => $transferTransaction?->id,
                'client_reference' => $data['client_reference'] ?? null,
                'amount' => $amount,
                'payment_method' => $paymentMode === 'wallet_transfer' ? 'Transfer Dompet' : $paymentMethod,
                'notes' => $notes,
                'settled_at' => $settledAt,
            ]);

            TransactionSplit::whereKey($splits->modelKeys())->update(['settled' => true]);

            return $settlement->load(['fromUser', 'toUser']);
        });
    }
}
