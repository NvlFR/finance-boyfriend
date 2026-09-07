<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\User;
use App\Models\Wallet;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentService
{
    /** @param array<string, mixed> $data */
    public function recordTransaction(User $user, Investment $investment, array $data): InvestmentTransaction
    {
        $clientReference = (string) $data['client_reference'];
        $existingTransaction = $this->findByClientReference($user, $clientReference);

        if ($existingTransaction) {
            return $existingTransaction;
        }

        try {
            return DB::transaction(function () use ($user, $investment, $data, $clientReference): InvestmentTransaction {
                $lockedInvestment = Investment::query()
                    ->whereKey($investment->id)
                    ->where('couple_space_id', $user->current_couple_space_id)
                    ->where(function ($query) use ($user): void {
                        $query->where('scope', 'shared')->orWhere('user_id', $user->id);
                    })
                    ->lockForUpdate()
                    ->firstOrFail();
                $wallet = Wallet::query()
                    ->whereKey($data['wallet_id'])
                    ->where('couple_space_id', $lockedInvestment->couple_space_id)
                    ->where('is_active', true)
                    ->where(function ($query) use ($user): void {
                        $query->where('type', 'joint')->orWhere('user_id', $user->id);
                    })
                    ->lockForUpdate()
                    ->firstOrFail();

                $unitPrice = $this->normalizeMoney($data['unit_price']);
                $feeAmount = $this->normalizeMoney($data['fee_amount'] ?? 0);
                $isRupiahPurchase = $data['type'] === 'buy' && $data['input_mode'] === 'amount';
                $grossAmount = $isRupiahPurchase
                    ? BigDecimal::of($this->normalizeMoney($data['amount']))
                    : BigDecimal::of($this->normalizeQuantity($data['quantity']))
                        ->multipliedBy($unitPrice)
                        ->toScale(2, RoundingMode::HalfUp);
                $quantity = $isRupiahPurchase
                    ? $grossAmount->dividedBy($unitPrice, 8, RoundingMode::Down)->__toString()
                    : $this->normalizeQuantity($data['quantity']);

                if (BigDecimal::of($quantity)->isZero()) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal pembelian terlalu kecil untuk harga per unit tersebut.',
                    ]);
                }

                $realizedProfitLoss = BigDecimal::zero()->toScale(2);
                if ($data['type'] === 'buy') {
                    $totalDebit = $grossAmount->plus($feeAmount);
                    $this->ensureSufficientWalletBalance(
                        $wallet,
                        $totalDebit,
                        $isRupiahPurchase ? 'amount' : 'quantity',
                    );
                    $this->decreaseWalletBalance($wallet, $totalDebit);
                    $this->applyBuy($lockedInvestment, BigDecimal::of($quantity), $totalDebit, BigDecimal::of($unitPrice));
                } else {
                    $realizedProfitLoss = $this->applySell(
                        $lockedInvestment,
                        BigDecimal::of($quantity),
                        BigDecimal::of($unitPrice),
                        BigDecimal::of($feeAmount),
                    );
                    $netProceeds = $grossAmount->minus($feeAmount);
                    if ($netProceeds->isNegative()) {
                        throw ValidationException::withMessages([
                            'fee_amount' => 'Biaya transaksi tidak boleh melebihi nilai penjualan.',
                        ]);
                    }
                    $this->increaseWalletBalance($wallet, $netProceeds);
                }

                return InvestmentTransaction::create([
                    'investment_id' => $lockedInvestment->id,
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => $data['type'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'gross_amount' => $grossAmount->__toString(),
                    'fee_amount' => $feeAmount,
                    'realized_profit_loss' => $realizedProfitLoss->__toString(),
                    'transaction_date' => $data['transaction_date'],
                    'client_reference' => $clientReference,
                    'notes' => $data['notes'] ?? null,
                ]);
            }, attempts: 3);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                $existingTransaction = $this->findByClientReference($user, $clientReference);

                if ($existingTransaction) {
                    return $existingTransaction;
                }
            }

            throw $exception;
        }
    }

    private function applyBuy(
        Investment $investment,
        BigDecimal $boughtQuantity,
        BigDecimal $totalDebit,
        BigDecimal $unitPrice,
    ): void {
        $oldQuantity = BigDecimal::of($investment->quantity);
        $newQuantity = $oldQuantity->plus($boughtQuantity);
        $oldCostBasis = $oldQuantity->multipliedBy($investment->average_buy_price);
        $newAveragePrice = $oldCostBasis
            ->plus($totalDebit)
            ->dividedBy($newQuantity, 2, RoundingMode::HalfUp);

        $investment->quantity = $newQuantity->toScale(8)->__toString();
        $investment->average_buy_price = $newAveragePrice->__toString();
        $investment->current_price = $unitPrice->toScale(2)->__toString();
        $investment->save();
    }

    private function applySell(
        Investment $investment,
        BigDecimal $soldQuantity,
        BigDecimal $unitPrice,
        BigDecimal $feeAmount,
    ): BigDecimal {
        $oldQuantity = BigDecimal::of($investment->quantity);
        if ($oldQuantity->isLessThan($soldQuantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah yang dijual melebihi unit investasi yang tersedia.',
            ]);
        }

        $realizedProfitLoss = $unitPrice
            ->minus($investment->average_buy_price)
            ->multipliedBy($soldQuantity)
            ->minus($feeAmount)
            ->toScale(2, RoundingMode::HalfUp);
        $remainingQuantity = $oldQuantity->minus($soldQuantity);

        $investment->quantity = $remainingQuantity->toScale(8)->__toString();
        $investment->average_buy_price = $remainingQuantity->isZero()
            ? '0.00'
            : $investment->average_buy_price;
        $investment->current_price = $unitPrice->toScale(2)->__toString();
        $investment->realized_profit_loss = BigDecimal::of($investment->realized_profit_loss)
            ->plus($realizedProfitLoss)
            ->toScale(2, RoundingMode::HalfUp)
            ->__toString();
        $investment->save();

        return $realizedProfitLoss;
    }

    private function ensureSufficientWalletBalance(Wallet $wallet, BigDecimal $amount, string $errorField): void
    {
        if (BigDecimal::of($wallet->balance)->isLessThan($amount)) {
            throw ValidationException::withMessages([
                $errorField => "Saldo dompet {$wallet->name} tidak mencukupi untuk pembelian ini.",
            ]);
        }
    }

    private function decreaseWalletBalance(Wallet $wallet, BigDecimal $amount): void
    {
        $wallet->balance = BigDecimal::of($wallet->balance)
            ->minus($amount)
            ->toScale(2, RoundingMode::HalfUp)
            ->__toString();
        $wallet->save();
    }

    private function increaseWalletBalance(Wallet $wallet, BigDecimal $amount): void
    {
        $wallet->balance = BigDecimal::of($wallet->balance)
            ->plus($amount)
            ->toScale(2, RoundingMode::HalfUp)
            ->__toString();
        $wallet->save();
    }

    private function normalizeMoney(mixed $amount): string
    {
        return BigDecimal::of((string) $amount)->toScale(2, RoundingMode::HalfUp)->__toString();
    }

    private function normalizeQuantity(mixed $quantity): string
    {
        return BigDecimal::of((string) $quantity)->toScale(8, RoundingMode::Unnecessary)->__toString();
    }

    private function findByClientReference(User $user, string $clientReference): ?InvestmentTransaction
    {
        return InvestmentTransaction::query()
            ->where('user_id', $user->id)
            ->where('client_reference', $clientReference)
            ->first();
    }
}
