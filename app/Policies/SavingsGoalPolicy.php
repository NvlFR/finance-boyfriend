<?php

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;

class SavingsGoalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->current_couple_space_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->current_couple_space_id === $savingsGoal->couple_space_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->current_couple_space_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavingsGoal $savingsGoal): bool
    {
        return $this->canManage($user, $savingsGoal);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavingsGoal $savingsGoal): bool
    {
        return $this->canManage($user, $savingsGoal);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavingsGoal $savingsGoal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavingsGoal $savingsGoal): bool
    {
        return false;
    }

    private function canManage(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->current_couple_space_id === $savingsGoal->couple_space_id
            && ($savingsGoal->scope === 'shared' || $savingsGoal->created_by_user_id === $user->id);
    }
}
