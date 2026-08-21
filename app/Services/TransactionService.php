<?php

namespace App\Services;

use App\Models\CoupleSpace;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    /**
     * Create a transaction, update wallet balances, and create split details if shared.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransaction(User $user, CoupleSpace $space, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $space, $data) {
            $type = $data['type'];
            $scope = $data['scope'];
            $amount = (float) $data['amount'];
            $walletId = (int) $data['wallet_id'];
            $toWalletId = ! empty($data['to_wallet_id']) ? (int) $data['to_wallet_id'] : null;

            $sourceWallet = Wallet::where('couple_space_id', $space->id)
                ->where('id', $walletId)
                ->lockForUpdate()
                ->firstOrFail();

            $destWallet = null;
            if ($type === 'transfer') {
                if (! $toWalletId || $toWalletId === $walletId) {
                    throw new InvalidArgumentException('Destination wallet must be provided and distinct for transfers.');
                }
                $destWallet = Wallet::where('couple_space_id', $space->id)
                    ->where('id', $toWalletId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // Adjust balances
            if ($type === 'expense') {
                $sourceWallet->decrement('balance', $amount);
            } elseif ($type === 'income') {
                $sourceWallet->increment('balance', $amount);
            } elseif ($type === 'transfer') {
                $sourceWallet->decrement('balance', $amount);
                $destWallet->increment('balance', $amount);
            }

            // Create Transaction
            $transaction = Transaction::create([
                'couple_space_id' => $space->id,
                'user_id' => $user->id,
                'wallet_id' => $sourceWallet->id,
                'to_wallet_id' => $destWallet?->id,
                'category_id' => $data['category_id'] ?? null,
                'type' => $type,
                'scope' => $scope,
                'amount' => $amount,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'title' => $data['title'] ?? null,
                'notes' => $data['notes'] ?? null,
                'receipt_image_path' => $data['receipt_image_path'] ?? null,
            ]);

            // Handle Split for shared expenses or shared transactions
            if ($scope === 'shared' && $type === 'expense') {
                $this->createSplitRecord($transaction, $user, $space, $data['split'] ?? []);
            }

            return $transaction->load(['wallet', 'toWallet', 'category', 'split', 'user']);
        });
    }

    /**
     * Update an existing transaction and recalculate wallet balance adjustments.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $space = $transaction->coupleSpace;

            // Revert original balances first
            $oldAmount = (float) $transaction->amount;
            $oldType = $transaction->type;
            $oldSourceWallet = Wallet::where('id', $transaction->wallet_id)->lockForUpdate()->first();

            if ($oldSourceWallet) {
                if ($oldType === 'expense') {
                    $oldSourceWallet->increment('balance', $oldAmount);
                } elseif ($oldType === 'income') {
                    $oldSourceWallet->decrement('balance', $oldAmount);
                } elseif ($oldType === 'transfer') {
                    $oldSourceWallet->increment('balance', $oldAmount);
                }
            }

            if ($oldType === 'transfer' && $transaction->to_wallet_id) {
                $oldDestWallet = Wallet::where('id', $transaction->to_wallet_id)->lockForUpdate()->first();
                if ($oldDestWallet) {
                    $oldDestWallet->decrement('balance', $oldAmount);
                }
            }

            // Prepare new values
            $newType = $data['type'] ?? $transaction->type;
            $newScope = $data['scope'] ?? $transaction->scope;
            $newAmount = isset($data['amount']) ? (float) $data['amount'] : (float) $transaction->amount;
            $newWalletId = isset($data['wallet_id']) ? (int) $data['wallet_id'] : $transaction->wallet_id;
            $newToWalletId = ! empty($data['to_wallet_id']) ? (int) $data['to_wallet_id'] : null;

            $newSourceWallet = Wallet::where('couple_space_id', $space->id)
                ->where('id', $newWalletId)
                ->lockForUpdate()
                ->firstOrFail();

            $newDestWallet = null;
            if ($newType === 'transfer') {
                if (! $newToWalletId || $newToWalletId === $newWalletId) {
                    throw new InvalidArgumentException('Destination wallet must be provided and distinct for transfers.');
                }
                $newDestWallet = Wallet::where('couple_space_id', $space->id)
                    ->where('id', $newToWalletId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // Apply new balances
            if ($newType === 'expense') {
                $newSourceWallet->decrement('balance', $newAmount);
            } elseif ($newType === 'income') {
                $newSourceWallet->increment('balance', $newAmount);
            } elseif ($newType === 'transfer') {
                $newSourceWallet->decrement('balance', $newAmount);
                $newDestWallet->increment('balance', $newAmount);
            }

            // Update Transaction
            $transaction->update([
                'wallet_id' => $newSourceWallet->id,
                'to_wallet_id' => $newDestWallet?->id,
                'category_id' => $data['category_id'] ?? $transaction->category_id,
                'type' => $newType,
                'scope' => $newScope,
                'amount' => $newAmount,
                'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
                'title' => $data['title'] ?? $transaction->title,
                'notes' => $data['notes'] ?? $transaction->notes,
            ]);

            // Update split record if shared expense
            if ($newScope === 'shared' && $newType === 'expense') {
                $transaction->split()->delete();
                $this->createSplitRecord($transaction, $transaction->user, $space, $data['split'] ?? []);
            } elseif ($transaction->split) {
                $transaction->split()->delete();
            }

            return $transaction->fresh(['wallet', 'toWallet', 'category', 'split', 'user']);
        });
    }

    /**
     * Delete a transaction and revert wallet balance adjustments.
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $amount = (float) $transaction->amount;
            $type = $transaction->type;

            $sourceWallet = Wallet::where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            if ($sourceWallet) {
                if ($type === 'expense') {
                    $sourceWallet->increment('balance', $amount);
                } elseif ($type === 'income') {
                    $sourceWallet->decrement('balance', $amount);
                } elseif ($type === 'transfer') {
                    $sourceWallet->increment('balance', $amount);
                }
            }

            if ($type === 'transfer' && $transaction->to_wallet_id) {
                $destWallet = Wallet::where('id', $transaction->to_wallet_id)
                    ->lockForUpdate()
                    ->first();
                if ($destWallet) {
                    $destWallet->decrement('balance', $amount);
                }
            }

            $transaction->delete();
        });
    }

    /**
     * Create TransactionSplit record based on split type and shares.
     *
     * @param  array<string, mixed>  $splitData
     */
    protected function createSplitRecord(Transaction $transaction, User $user, CoupleSpace $space, array $splitData): TransactionSplit
    {
        $amount = (float) $transaction->amount;
        $paidByUserId = ! empty($splitData['paid_by_user_id']) ? (int) $splitData['paid_by_user_id'] : $user->id;
        $splitType = $splitData['split_type'] ?? 'split_equal';

        $userOneAmount = 0.00;
        $userTwoAmount = 0.00;

        switch ($splitType) {
            case 'full_one':
                $userOneAmount = $amount;
                $userTwoAmount = 0.00;
                break;
            case 'full_two':
                $userOneAmount = 0.00;
                $userTwoAmount = $amount;
                break;
            case 'custom':
                $userOneAmount = isset($splitData['user_one_amount']) ? (float) $splitData['user_one_amount'] : ($amount / 2);
                $userTwoAmount = isset($splitData['user_two_amount']) ? (float) $splitData['user_two_amount'] : ($amount - $userOneAmount);
                break;
            case 'joint_fund':
                $userOneAmount = 0.00;
                $userTwoAmount = 0.00;
                break;
            case 'split_equal':
            default:
                $splitType = 'split_equal';
                $half = round($amount / 2, 2);
                $userOneAmount = $half;
                $userTwoAmount = round($amount - $half, 2);
                break;
        }

        return TransactionSplit::create([
            'transaction_id' => $transaction->id,
            'paid_by_user_id' => $paidByUserId,
            'user_one_amount' => $userOneAmount,
            'user_two_amount' => $userTwoAmount,
            'split_type' => $splitType,
            'settled' => false,
        ]);
    }
}
