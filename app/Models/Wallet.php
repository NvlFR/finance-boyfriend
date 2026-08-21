<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $couple_space_id
 * @property int|null $user_id
 * @property string $name
 * @property string $type
 * @property string $wallet_type
 * @property string|null $account_number
 * @property string $balance
 * @property string $currency
 * @property string $color
 * @property string $icon
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'couple_space_id',
    'user_id',
    'name',
    'type',
    'wallet_type',
    'account_number',
    'balance',
    'currency',
    'color',
    'icon',
    'is_active',
])]
class Wallet extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Couple space this wallet belongs to.
     *
     * @return BelongsTo<CoupleSpace, $this>
     */
    public function coupleSpace(): BelongsTo
    {
        return $this->belongsTo(CoupleSpace::class);
    }

    /**
     * Owner of the wallet (null if joint wallet).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transactions originating from this wallet.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
