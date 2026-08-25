<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\CoupleSpace;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\TransactionSplit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Wishlist;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $clientReference = $data['client_reference'] ?? null;

        if ($clientReference) {
            $existingTransaction = $this->findByClientReference($user, $space, $clientReference);

            if ($existingTransaction) {
                return $existingTransaction;
            }
        }

        try {
            return DB::transaction(function () use ($user, $space, $data, $clientReference) {
                $type = $data['type'];
                $scope = $data['scope'];
                $amount = (float) $data['amount'];
                $walletId = (int) $data['wallet_id'];
                $toWalletId = ! empty($data['to_wallet_id']) ? (int) $data['to_wallet_id'] : null;

                $sourceWallet = Wallet::where('couple_space_id', $space->id)
                    ->where('id', $walletId)
                    ->where(function ($query) use ($user) {
                        $query->where('type', 'joint')->orWhere('user_id', $user->id);
                    })
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
                    $this->ensureSufficientBalance($sourceWallet, $amount);
                    $sourceWallet->decrement('balance', $amount);
                } elseif ($type === 'income') {
                    $sourceWallet->increment('balance', $amount);
                } elseif ($type === 'transfer') {
                    $this->ensureSufficientBalance($sourceWallet, $amount);
                    $sourceWallet->decrement('balance', $amount);
                    $destWallet->increment('balance', $amount);
                }

                // Create Transaction
                $transaction = Transaction::create([
                    'couple_space_id' => $space->id,
                    'user_id' => $user->id,
                    'wallet_id' => $sourceWallet->id,
                    'to_wallet_id' => $destWallet?->id,
                    'category_id' => $type === 'transfer' ? null : ($data['category_id'] ?? null),
                    'type' => $type,
                    'scope' => $scope,
                    'amount' => $amount,
                    'transaction_date' => $data['transaction_date'] ?? now(),
                    'title' => $data['title'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'receipt_image_path' => $data['receipt_image_path'] ?? null,
                    'client_reference' => $clientReference,
                    'source_type' => $data['source_type'] ?? null,
                    'source_id' => $data['source_id'] ?? null,
                ]);

                // Handle Split for shared expenses or shared transactions
                if ($scope === 'shared' && $type === 'expense') {
                    $this->createSplitRecord($transaction, $user, $space, $data['split'] ?? []);
                }

                $this->applyFeatureContext($transaction, $user, $space);

                return $transaction->load(['wallet', 'toWallet', 'category', 'split', 'user']);
            });
        } catch (QueryException $exception) {
            if ($clientReference && $exception->getCode() === '23000') {
                $existingTransaction = $this->findByClientReference($user, $space, $clientReference);

                if ($existingTransaction) {
                    return $existingTransaction;
                }
            }

            throw $exception;
        }
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
            $oldSourceWallet = Wallet::where('couple_space_id', $space->id)
                ->where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            if ($oldSourceWallet) {
                if ($oldType === 'expense') {
                    $oldSourceWallet->increment('balance', $oldAmount);
                } elseif ($oldType === 'income') {
                    $this->ensureSufficientBalance($oldSourceWallet, $oldAmount);
                    $oldSourceWallet->decrement('balance', $oldAmount);
                } elseif ($oldType === 'transfer') {
                    $oldSourceWallet->increment('balance', $oldAmount);
                }
            }

            if ($oldType === 'transfer' && $transaction->to_wallet_id) {
                $oldDestWallet = Wallet::where('couple_space_id', $space->id)
                    ->where('id', $transaction->to_wallet_id)
                    ->lockForUpdate()
                    ->first();
                if ($oldDestWallet) {
                    $this->ensureSufficientBalance($oldDestWallet, $oldAmount);
                    $oldDestWallet->decrement('balance', $oldAmount);
                }
            }

            // Prepare new values
            $newType = $data['type'] ?? $transaction->type;
            $newScope = $data['scope'] ?? $transaction->scope;
            $newAmount = isset($data['amount']) ? (float) $data['amount'] : (float) $transaction->amount;
            $newWalletId = isset($data['wallet_id']) ? (int) $data['wallet_id'] : $transaction->wallet_id;
            $newToWalletId = ! empty($data['to_wallet_id']) ? (int) $data['to_wallet_id'] : null;

            if ($transaction->source_type && $newType !== 'expense') {
                throw ValidationException::withMessages([
                    'type' => 'Transaksi pembayaran fitur harus tetap berupa pengeluaran.',
                ]);
            }

            $newSourceWallet = Wallet::where('couple_space_id', $space->id)
                ->where('id', $newWalletId)
                ->where(function ($query) use ($transaction) {
                    $query->where('type', 'joint')->orWhere('user_id', $transaction->user_id);
                })
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
                $this->ensureSufficientBalance($newSourceWallet, $newAmount);
                $newSourceWallet->decrement('balance', $newAmount);
            } elseif ($newType === 'income') {
                $newSourceWallet->increment('balance', $newAmount);
            } elseif ($newType === 'transfer') {
                $this->ensureSufficientBalance($newSourceWallet, $newAmount);
                $newSourceWallet->decrement('balance', $newAmount);
                $newDestWallet->increment('balance', $newAmount);
            }

            // Update Transaction
            $transaction->update([
                'wallet_id' => $newSourceWallet->id,
                'to_wallet_id' => $newDestWallet?->id,
                'category_id' => $newType === 'transfer'
                    ? null
                    : (array_key_exists('category_id', $data) ? $data['category_id'] : $transaction->category_id),
                'type' => $newType,
                'scope' => $newScope,
                'amount' => $newAmount,
                'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
                'title' => $data['title'] ?? $transaction->title,
                'notes' => $data['notes'] ?? $transaction->notes,
            ]);

            // Update split record if shared expense
            if ($newScope === 'shared' && $newType === 'expense') {
                if (array_key_exists('split', $data) || ! $transaction->split) {
                    $transaction->split()->delete();
                    $this->createSplitRecord($transaction, $transaction->user, $space, $data['split'] ?? []);
                }
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
        if ($transaction->source_type) {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi yang terhubung ke fitur tidak dapat dihapus agar status pembayaran tetap konsisten.',
            ]);
        }

        DB::transaction(function () use ($transaction) {
            $amount = (float) $transaction->amount;
            $type = $transaction->type;

            $sourceWallet = Wallet::where('couple_space_id', $transaction->couple_space_id)
                ->where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            if ($sourceWallet) {
                if ($type === 'expense') {
                    $sourceWallet->increment('balance', $amount);
                } elseif ($type === 'income') {
                    $this->ensureSufficientBalance($sourceWallet, $amount);
                    $sourceWallet->decrement('balance', $amount);
                } elseif ($type === 'transfer') {
                    $sourceWallet->increment('balance', $amount);
                }
            }

            if ($type === 'transfer' && $transaction->to_wallet_id) {
                $destWallet = Wallet::where('couple_space_id', $transaction->couple_space_id)
                    ->where('id', $transaction->to_wallet_id)
                    ->lockForUpdate()
                    ->first();
                if ($destWallet) {
                    $this->ensureSufficientBalance($destWallet, $amount);
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

    private function ensureSufficientBalance(Wallet $wallet, float $amount): void
    {
        if ((float) $wallet->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => "Saldo dompet {$wallet->name} tidak mencukupi.",
            ]);
        }
    }

    private function findByClientReference(User $user, CoupleSpace $space, string $clientReference): ?Transaction
    {
        return Transaction::query()
            ->where('couple_space_id', $space->id)
            ->where('user_id', $user->id)
            ->where('client_reference', $clientReference)
            ->with(['wallet', 'toWallet', 'category', 'split', 'user'])
            ->first();
    }

    private function applyFeatureContext(Transaction $transaction, User $user, CoupleSpace $space): void
    {
        if (! $transaction->source_type || ! $transaction->source_id) {
            return;
        }

        if ($transaction->type !== 'expense') {
            throw ValidationException::withMessages([
                'type' => 'Pembayaran fitur harus dicatat sebagai pengeluaran.',
            ]);
        }

        if ($transaction->source_type === 'subscription') {
            $subscription = Subscription::query()
                ->where('couple_space_id', $space->id)
                ->lockForUpdate()
                ->findOrFail($transaction->source_id);
            $nextBillingDate = $subscription->next_billing_date->copy();

            do {
                $nextBillingDate = $subscription->billing_cycle === 'yearly'
                    ? $nextBillingDate->addYearNoOverflow()
                    : $nextBillingDate->addMonthNoOverflow();
            } while ($nextBillingDate->lessThanOrEqualTo($transaction->transaction_date));

            $subscription->update([
                'paid_by_user_id' => $user->id,
                'wallet_id' => $transaction->wallet_id,
                'last_paid_at' => $transaction->transaction_date,
                'next_billing_date' => $nextBillingDate,
            ]);

            return;
        }

        if ($transaction->source_type === 'wishlist') {
            $wishlist = Wishlist::query()
                ->where('couple_space_id', $space->id)
                ->lockForUpdate()
                ->findOrFail($transaction->source_id);

            if ($wishlist->is_secret_surprise && $wishlist->target_user_id === $user->id) {
                abort(403, 'Kado kejutan hanya dapat dibeli oleh pembuatnya.');
            }

            $wishlist->update(['is_bought' => true]);

            return;
        }

        Budget::query()
            ->where('couple_space_id', $space->id)
            ->lockForUpdate()
            ->findOrFail($transaction->source_id);
    }
}
