<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $couple_space_id
 * @property int $user_id
 * @property string $title
 * @property string $estimated_price
 * @property string $priority
 * @property string|null $url
 * @property string|null $image_path
 * @property string|null $notes
 * @property bool $is_secret_surprise
 * @property int|null $target_user_id
 * @property bool $is_bought
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'couple_space_id',
    'user_id',
    'title',
    'estimated_price',
    'priority',
    'url',
    'image_path',
    'notes',
    'is_secret_surprise',
    'target_user_id',
    'is_bought',
])]
class Wishlist extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_price' => 'decimal:2',
            'is_secret_surprise' => 'boolean',
            'is_bought' => 'boolean',
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
     * Creator of the wishlist item.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Target partner (if secret surprise gift).
     *
     * @return BelongsTo<User, $this>
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
