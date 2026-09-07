<?php

namespace App\Models;

use Database\Factories\InvestmentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_id
 * @property int $user_id
 * @property int|null $wallet_id
 * @property string $type
 * @property string $quantity
 * @property string $unit_price
 * @property string $gross_amount
 * @property string $fee_amount
 * @property string $realized_profit_loss
 * @property Carbon $transaction_date
 * @property string|null $client_reference
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'investment_id',
    'user_id',
    'wallet_id',
    'type',
    'quantity',
    'unit_price',
    'gross_amount',
    'fee_amount',
    'realized_profit_loss',
    'transaction_date',
    'client_reference',
    'notes',
])]
class InvestmentTransaction extends Model
{
    /** @use HasFactory<InvestmentTransactionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'unit_price' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'realized_profit_loss' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    /** @return BelongsTo<Investment, $this> */
    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
