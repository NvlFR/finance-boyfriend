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
 * @property string|null $origin_name
 * @property string|null $destination_name
 * @property float|null $origin_lat
 * @property float|null $origin_lng
 * @property float|null $destination_lat
 * @property float|null $destination_lng
 * @property float|null $current_lat
 * @property float|null $current_lng
 * @property float $speed
 * @property float $max_speed
 * @property float $total_distance_km
 * @property string $status
 * @property string|null $notes
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'couple_space_id',
    'user_id',
    'title',
    'origin_name',
    'destination_name',
    'origin_lat',
    'origin_lng',
    'destination_lat',
    'destination_lng',
    'current_lat',
    'current_lng',
    'speed',
    'max_speed',
    'total_distance_km',
    'status',
    'notes',
    'started_at',
    'ended_at',
])]
class Trip extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin_lat' => 'float',
            'origin_lng' => 'float',
            'destination_lat' => 'float',
            'destination_lng' => 'float',
            'current_lat' => 'float',
            'current_lng' => 'float',
            'speed' => 'float',
            'max_speed' => 'float',
            'total_distance_km' => 'float',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Couple space of the trip.
     *
     * @return BelongsTo<CoupleSpace, $this>
     */
    public function coupleSpace(): BelongsTo
    {
        return $this->belongsTo(CoupleSpace::class);
    }

    /**
     * User who is taking the trip.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
