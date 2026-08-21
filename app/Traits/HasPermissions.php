<?php

namespace App\Traits;

/**
 * Trait for handling user permissions and role checks
 */
trait HasPermissions
{
    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->role === 'Staff';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    /**
     * Check if user is inactive
     */
    public function isInactive(): bool
    {
        return $this->status === 'Inactive';
    }

    /**
     * Check if user has a specific (legacy) role string
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has a specific status
     */
    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Check if user can manage staff
     * Only admins can manage other staff
     */
    public function canManageStaff(): bool
    {
        return $this->isAdmin() && $this->isActive();
    }

    /**
     * Check if user can be deleted
     * Prevent deletion of the only admin
     */
    public function canBeDeleted(): bool
    {
        if ($this->isAdmin()) {
            return \App\Models\User::where('role', 'Admin')->count() > 1;
        }
        return true;
    }

    /**
     * Check whether this user can access a given page/section.
     *
     * - Admins always have full access.
     * - Staff with a role_id: access is determined by the role's permissions array.
     * - Staff without a role_id: default to POS + Dashboard only.
     */
    public function canAccess(string $page): bool
    {
        // Admin bypasses all permission checks
        if ($this->isAdmin()) {
            return true;
        }

        // Load the assigned permission role
        $permRole = $this->permissionRole;

        if (!$permRole) {
            // No role assigned — grant absolute minimum access
            return in_array($page, ['dashboard', 'pos']);
        }

        return in_array($page, $permRole->permissions ?? []);
    }

    /**
     * Relationship: the permission Role assigned to this user.
     * Defined here so the trait works without modifying the model directly.
     */
    public function permissionRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }
}
