<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $nickname
 * @property string $email
 * @property string|null $avatar_url
 * @property string $theme_color
 * @property int|null $current_couple_space_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'nickname', 'email', 'google_id', 'avatar_url', 'theme_color', 'current_couple_space_id', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'google_id',
        'avatar_url',
        'theme_color',
        'current_couple_space_id',
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Current active couple space for this user.
     *
     * @return BelongsTo<CoupleSpace, $this>
     */
    public function currentCoupleSpace(): BelongsTo
    {
        return $this->belongsTo(CoupleSpace::class, 'current_couple_space_id');
    }

    /**
     * Wallets owned by this user.
     *
     * @return HasMany<Wallet, $this>
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Transactions created by this user.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Investment, $this> */
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    /** @return HasMany<InvestmentTransaction, $this> */
    public function investmentTransactions(): HasMany
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    /** @return HasMany<BirthdaySurprise, $this> */
    public function createdBirthdaySurprises(): HasMany
    {
        return $this->hasMany(BirthdaySurprise::class, 'creator_user_id');
    }

    /** @return HasMany<BirthdaySurprise, $this> */
    public function receivedBirthdaySurprises(): HasMany
    {
        return $this->hasMany(BirthdaySurprise::class, 'recipient_user_id');
    }

    /**
     * Get active couple space or automatically create one for user.
     */
    public function getOrEnsureCoupleSpace(): CoupleSpace
    {
        return DB::transaction(function (): CoupleSpace {
            $lockedUser = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->current_couple_space_id) {
                return CoupleSpace::findOrFail($lockedUser->current_couple_space_id);
            }

            $existing = CoupleSpace::where('user_one_id', $lockedUser->id)
                ->orWhere('user_two_id', $lockedUser->id)
                ->first();

            if ($existing) {
                $lockedUser->update(['current_couple_space_id' => $existing->id]);
                $this->setAttribute('current_couple_space_id', $existing->id);

                return $existing;
            }

            $displayName = $lockedUser->nickname ?: explode(' ', $lockedUser->name)[0];
            $space = CoupleSpace::create([
                'name' => "Ruang {$displayName} & Pasangan",
                'invite_code' => CoupleSpace::generateInviteCode(),
                'user_one_id' => $lockedUser->id,
                'status' => 'pending',
            ]);

            $lockedUser->update(['current_couple_space_id' => $space->id]);
            $this->setAttribute('current_couple_space_id', $space->id);

            return $space;
        }, attempts: 3);
    }

    public function nicknameOrName(): string
    {
        return $this->nickname ?: $this->name;
    }
}
