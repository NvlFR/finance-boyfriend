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
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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
                $amount = $this->normalizeMoney($data['amount']);
                $feeAmount = $type === 'transfer'
                    ? $this->normalizeMoney($data['fee_amount'] ?? 0)
                    : '0.00';
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
                    $this->decreaseBalance($sourceWallet, $amount);
                } elseif ($type === 'income') {
                    $this->increaseBalance($sourceWallet, $amount);
                } elseif ($type === 'transfer') {
                    $sourceDebit = $this->addMoney($amount, $feeAmount);
                    $this->ensureSufficientBalance($sourceWallet, $sourceDebit);
                    $this->decreaseBalance($sourceWallet, $sourceDebit);
                    $this->increaseBalance($destWallet, $amount);
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
                    'fee_amount' => $feeAmount,
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
            $oldAmount = $this->normalizeMoney($transaction->amount);
            $oldFeeAmount = $this->normalizeMoney($transaction->fee_amount);
            $oldType = $transaction->type;
            $oldSourceWallet = Wallet::where('couple_space_id', $space->id)
                ->where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            if ($oldSourceWallet) {
                if ($oldType === 'expense') {
                    $this->increaseBalance($oldSourceWallet, $oldAmount);
                } elseif ($oldType === 'income') {
                    $this->ensureSufficientBalance($oldSourceWallet, $oldAmount);
                    $this->decreaseBalance($oldSourceWallet, $oldAmount);
                } elseif ($oldType === 'transfer') {
                    $this->increaseBalance($oldSourceWallet, $this->addMoney($oldAmount, $oldFeeAmount));
                }
            }

            if ($oldType === 'transfer' && $transaction->to_wallet_id) {
                $oldDestWallet = Wallet::where('couple_space_id', $space->id)
                    ->where('id', $transaction->to_wallet_id)
                    ->lockForUpdate()
                    ->first();
                if ($oldDestWallet) {
                    $this->ensureSufficientBalance($oldDestWallet, $oldAmount);
                    $this->decreaseBalance($oldDestWallet, $oldAmount);
                }
            }

            // Prepare new values
            $newType = $data['type'] ?? $transaction->type;
            $newScope = $data['scope'] ?? $transaction->scope;
            $newAmount = isset($data['amount'])
                ? $this->normalizeMoney($data['amount'])
                : $this->normalizeMoney($transaction->amount);
            $newFeeAmount = $newType === 'transfer'
                ? $this->normalizeMoney($data['fee_amount'] ?? $transaction->fee_amount)
                : '0.00';
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
                $this->decreaseBalance($newSourceWallet, $newAmount);
            } elseif ($newType === 'income') {
                $this->increaseBalance($newSourceWallet, $newAmount);
            } elseif ($newType === 'transfer') {
                $newSourceDebit = $this->addMoney($newAmount, $newFeeAmount);
                $this->ensureSufficientBalance($newSourceWallet, $newSourceDebit);
                $this->decreaseBalance($newSourceWallet, $newSourceDebit);
                $this->increaseBalance($newDestWallet, $newAmount);
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
                'fee_amount' => $newFeeAmount,
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
            $amount = $this->normalizeMoney($transaction->amount);
            $feeAmount = $this->normalizeMoney($transaction->fee_amount);
            $type = $transaction->type;

            $sourceWallet = Wallet::where('couple_space_id', $transaction->couple_space_id)
                ->where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->first();

            if ($sourceWallet) {
                if ($type === 'expense') {
                    $this->increaseBalance($sourceWallet, $amount);
                } elseif ($type === 'income') {
                    $this->ensureSufficientBalance($sourceWallet, $amount);
                    $this->decreaseBalance($sourceWallet, $amount);
                } elseif ($type === 'transfer') {
                    $this->increaseBalance($sourceWallet, $this->addMoney($amount, $feeAmount));
                }
            }

            if ($type === 'transfer' && $transaction->to_wallet_id) {
                $destWallet = Wallet::where('couple_space_id', $transaction->couple_space_id)
                    ->where('id', $transaction->to_wallet_id)
                    ->lockForUpdate()
                    ->first();
                if ($destWallet) {
                    $this->ensureSufficientBalance($destWallet, $amount);
                    $this->decreaseBalance($destWallet, $amount);
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
        $amount = $this->normalizeMoney($transaction->amount);
        $paidByUserId = ! empty($splitData['paid_by_user_id']) ? (int) $splitData['paid_by_user_id'] : $user->id;
        $splitType = $splitData['split_type'] ?? 'split_equal';

        $userOneAmount = '0.00';
        $userTwoAmount = '0.00';

        switch ($splitType) {
            case 'full_one':
                $userOneAmount = $amount;
                $userTwoAmount = '0.00';
                break;
            case 'full_two':
                $userOneAmount = '0.00';
                $userTwoAmount = $amount;
                break;
            case 'custom':
                $userOneAmount = isset($splitData['user_one_amount'])
                    ? $this->normalizeMoney($splitData['user_one_amount'])
                    : BigDecimal::of($amount)->dividedBy(2, 2, RoundingMode::Down)->__toString();
                $userTwoAmount = isset($splitData['user_two_amount'])
                    ? $this->normalizeMoney($splitData['user_two_amount'])
                    : BigDecimal::of($amount)->minus($userOneAmount)->__toString();
                break;
            case 'joint_fund':
                $userOneAmount = '0.00';
                $userTwoAmount = '0.00';
                break;
            case 'split_equal':
            default:
                $splitType = 'split_equal';
                $userOneAmount = BigDecimal::of($amount)->dividedBy(2, 2, RoundingMode::Down)->__toString();
                $userTwoAmount = BigDecimal::of($amount)->minus($userOneAmount)->__toString();
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

    private function ensureSufficientBalance(Wallet $wallet, string $amount): void
    {
        if (BigDecimal::of($wallet->balance)->isLessThan($amount)) {
            throw ValidationException::withMessages([
                'amount' => "Saldo dompet {$wallet->name} tidak mencukupi.",
            ]);
        }
    }

    private function increaseBalance(Wallet $wallet, string $amount): void
    {
        $wallet->balance = BigDecimal::of($wallet->balance)
            ->plus($amount)
            ->toScale(2)
            ->__toString();
        $wallet->save();
    }

    private function decreaseBalance(Wallet $wallet, string $amount): void
    {
        $wallet->balance = BigDecimal::of($wallet->balance)
            ->minus($amount)
            ->toScale(2)
            ->__toString();
        $wallet->save();
    }

    private function normalizeMoney(mixed $amount): string
    {
        return BigDecimal::of((string) $amount)
            ->toScale(2, RoundingMode::HalfUp)
            ->__toString();
    }

    private function addMoney(string $amount, string $additionalAmount): string
    {
        return BigDecimal::of($amount)->plus($additionalAmount)->__toString();
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
