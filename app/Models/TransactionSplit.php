<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $paid_by_user_id
 * @property string $user_one_amount
 * @property string $user_two_amount
 * @property string $split_type
 * @property bool $settled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'transaction_id',
    'paid_by_user_id',
    'user_one_amount',
    'user_two_amount',
    'split_type',
    'settled',
])]
class TransactionSplit extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_one_amount' => 'decimal:2',
            'user_two_amount' => 'decimal:2',
            'settled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * User who physically made the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }
}
