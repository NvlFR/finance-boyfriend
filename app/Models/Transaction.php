<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $couple_space_id
 * @property int $user_id
 * @property int|null $wallet_id
 * @property int|null $emergency_savings_goal_id
 * @property int|null $to_wallet_id
 * @property int|null $category_id
 * @property string $type
 * @property string $scope
 * @property string $amount
 * @property string $fee_amount
 * @property Carbon $transaction_date
 * @property string|null $title
 * @property string|null $notes
 * @property string|null $receipt_image_path
 * @property string|null $client_reference
 * @property string|null $source_type
 * @property int|null $source_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Wallet|null $wallet
 * @property-read SavingsGoal|null $emergencySavingsGoal
 * @property-read Wallet|null $toWallet
 * @property-read Category|null $category
 * @property-read TransactionSplit|null $split
 */
#[Fillable([
    'couple_space_id',
    'user_id',
    'wallet_id',
    'emergency_savings_goal_id',
    'to_wallet_id',
    'category_id',
    'type',
    'scope',
    'amount',
    'fee_amount',
    'transaction_date',
    'title',
    'notes',
    'receipt_image_path',
    'client_reference',
    'source_type',
    'source_id',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'scope' => 'personal',
        'fee_amount' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CoupleSpace, $this>
     */
    public function coupleSpace(): BelongsTo
    {
        return $this->belongsTo(CoupleSpace::class);
    }

    /**
     * User who logged the transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Source wallet.
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Tabungan darurat yang digunakan sebagai sumber pembayaran.
     *
     * @return BelongsTo<SavingsGoal, $this>
     */
    public function emergencySavingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class, 'emergency_savings_goal_id');
    }

    /**
     * Destination wallet (for transfer).
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Split bill details if this is a shared expense.
     *
     * @return HasOne<TransactionSplit, $this>
     */
    public function split(): HasOne
    {
        return $this->hasOne(TransactionSplit::class);
    }
}
