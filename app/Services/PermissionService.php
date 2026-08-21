<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization and Permission Service
 * 
 * Provides methods for checking user permissions and roles
 */
class PermissionService
{
    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    /**
     * Check if current user is staff
     */
    public static function isStaff(): bool
    {
        return Auth::check() && Auth::user()->isStaff();
    }

    /**
     * Check if current user can manage staff
     */
    public static function canManageStaff(): bool
    {
        return Auth::check() && Auth::user()->canManageStaff();
    }

    /**
     * Check if user has a specific role
     */
    public static function userHasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Check if user has a specific status
     */
    public static function userHasStatus(User $user, string $status): bool
    {
        return $user->hasStatus($status);
    }

    /**
     * Check if a user can be deleted
     */
    public static function canDeleteUser(User $user): bool
    {
        return $user->canBeDeleted();
    }

    /**
     * Get all admin users
     */
    public static function getAdmins()
    {
        return User::where('role', 'Admin')->get();
    }

    /**
     * Get all staff members
     */
    public static function getStaff()
    {
        return User::where('role', 'Staff')->get();
    }

    /**
     * Get all active users
     */
    public static function getActiveUsers()
    {
        return User::where('status', 'Active')->get();
    }

    /**
     * Count total staff members
     */
    public static function countStaff(): int
    {
        return User::count();
    }

    /**
     * Count active staff members
     */
    public static function countActiveStaff(): int
    {
        return User::where('status', 'Active')->count();
    }

    /**
     * Count admin users
     */
    public static function countAdmins(): int
    {
        return User::where('role', 'Admin')->count();
    }
}
