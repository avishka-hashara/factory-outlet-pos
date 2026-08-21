<?php

namespace App\Policies;

use App\Models\User;

/**
 * User Policy for authorization testing
 * 
 * Allows blade directives and authorization checks:
 * @can('viewStaff', $user)
 * @can('manageStaff')
 */
class UserPolicy
{
    /**
     * Determine whether the user can view staff
     */
    public function viewStaff(User $user, User $staff = null): bool
    {
        // Any authenticated user can view their own profile
        if ($staff && $user->id === $staff->id) {
            return true;
        }

        // Only admins can view other staff
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage staff
     */
    public function manageStaff(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    /**
     * Determine whether the user can create staff
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    /**
     * Determine whether the user can update staff
     */
    public function update(User $user, User $staff): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    /**
     * Determine whether the user can delete staff
     */
    public function delete(User $user, User $staff): bool
    {
        // Only admins can delete other staff
        if (!$user->isAdmin() || !$user->isActive()) {
            return false;
        }

        // Prevent deletion of the only admin
        return $staff->canBeDeleted();
    }

    /**
     * Determine whether the user can change staff role
     */
    public function changeRole(User $user, User $staff): bool
    {
        return $user->isAdmin() && $user->isActive();
    }

    /**
     * Determine whether the user can change staff status
     */
    public function changeStatus(User $user, User $staff): bool
    {
        return $user->isAdmin() && $user->isActive();
    }
}
