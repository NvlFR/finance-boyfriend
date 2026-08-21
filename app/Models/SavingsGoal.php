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
 * @property int $created_by_user_id
 * @property string $name
 * @property string $target_amount
 * @property string $current_amount
 * @property Carbon|null $target_date
 * @property string $icon
 * @property string $color
 * @property string|null $cover_image_path
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'couple_space_id',
    'created_by_user_id',
    'name',
    'target_amount',
    'current_amount',
    'target_date',
    'icon',
    'color',
    'cover_image_path',
    'status',
])]
class SavingsGoal extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<SavingsContribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(SavingsContribution::class);
    }

    /**
     * Percentage completed.
     */
    public function getPercentageAttribute(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0.0;
        }

        return min(100.0, round(((float) $this->current_amount / (float) $this->target_amount) * 100, 1));
    }
}
