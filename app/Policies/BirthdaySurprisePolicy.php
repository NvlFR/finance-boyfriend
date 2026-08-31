<?php

namespace App\Policies;

use App\Models\BirthdaySurprise;
use App\Models\User;

class BirthdaySurprisePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $space = $user->currentCoupleSpace;

        return $space?->canManageBirthdaySurprise($user) ?? false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BirthdaySurprise $birthdaySurprise): bool
    {
        return $this->update($user, $birthdaySurprise);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BirthdaySurprise $birthdaySurprise): bool
    {
        $space = $user->currentCoupleSpace;

        return $space !== null
            && $birthdaySurprise->couple_space_id === $space->id
            && $space->canManageBirthdaySurprise($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BirthdaySurprise $birthdaySurprise): bool
    {
        return $this->update($user, $birthdaySurprise);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BirthdaySurprise $birthdaySurprise): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BirthdaySurprise $birthdaySurprise): bool
    {
        return false;
    }
}
