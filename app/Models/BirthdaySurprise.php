<?php

namespace App\Models;

use Database\Factories\BirthdaySurpriseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $couple_space_id
 * @property int $creator_user_id
 * @property int $recipient_user_id
 * @property string $opening_message
 * @property string $appreciation_message
 * @property string $love_letter
 * @property string $closing_message
 * @property array<int, string>|null $photos
 * @property array<int, string>|null $vouchers
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_enabled
 */
#[Fillable([
    'couple_space_id', 'creator_user_id', 'recipient_user_id',
    'opening_message', 'appreciation_message', 'love_letter', 'closing_message',
    'photos', 'vouchers', 'starts_at', 'ends_at', 'is_enabled',
])]
class BirthdaySurprise extends Model
{
    /** @use HasFactory<BirthdaySurpriseFactory> */
    use HasFactory;

    protected $attributes = ['is_enabled' => true];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'vouchers' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<CoupleSpace, $this> */
    public function coupleSpace(): BelongsTo
    {
        return $this->belongsTo(CoupleSpace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
