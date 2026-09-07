<?php

namespace App\Models;

use Database\Factories\InvestmentFactory;
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
 * @property string|null $symbol
 * @property string $asset_type
 * @property string $scope
 * @property string $quantity
 * @property string $average_buy_price
 * @property string $current_price
 * @property string $realized_profit_loss
 * @property string $currency
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'couple_space_id',
    'user_id',
    'name',
    'symbol',
    'asset_type',
    'scope',
    'quantity',
    'average_buy_price',
    'current_price',
    'realized_profit_loss',
    'currency',
    'is_active',
])]
class Investment extends Model
{
    /** @use HasFactory<InvestmentFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'scope' => 'personal',
        'quantity' => 0,
        'average_buy_price' => 0,
        'current_price' => 0,
        'realized_profit_loss' => 0,
        'currency' => 'IDR',
        'is_active' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'average_buy_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'realized_profit_loss' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<CoupleSpace, $this> */
    public function coupleSpace(): BelongsTo
    {
        return $this->belongsTo(CoupleSpace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<InvestmentTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }
}
