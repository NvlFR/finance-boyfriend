<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\CoupleSpace;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'nickname' => $input['nickname'] ?? explode(' ', $input['name'])[0],
                'email' => $input['email'],
                'password' => $input['password'],
                'theme_color' => '#6366F1',
            ]);

            // Create initial pending couple space
            $space = CoupleSpace::create([
                'name' => $user->name.' Space',
                'invite_code' => CoupleSpace::generateInviteCode(),
                'user_one_id' => $user->id,
                'user_two_id' => null,
                'status' => 'pending',
            ]);

            $user->update(['current_couple_space_id' => $space->id]);

            // Create default starter wallet
            Wallet::create([
                'couple_space_id' => $space->id,
                'user_id' => $user->id,
                'name' => 'Dompet Utama',
                'type' => 'personal',
                'wallet_type' => 'bank',
                'balance' => 0.00,
                'currency' => 'IDR',
                'color' => '#6366F1',
                'icon' => 'wallet',
                'is_active' => true,
            ]);

            return $user;
        });
    }
}
