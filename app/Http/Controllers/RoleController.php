<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Display all roles (admin-only page).
     */
    public function index()
    {
        $roles       = Role::withCount('users')->orderBy('name')->get();
        $permissions = Role::allPermissions();
        return view('roles.index', compact('roles', 'permissions'));
    }

    /**
     * Store a new role.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(Role::allPermissions())),
        ]);

        $role = Role::create([
            'name'        => $request->name,
            'description' => $request->description,
            'permissions' => $request->permissions ?? [],
            'is_system'   => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Role \"{$role->name}\" created successfully.",
            'role'    => $role->load('users'),
        ]);
    }

    /**
     * Show a single role (for edit modal population).
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'success' => true,
            'role'    => $role,
        ]);
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name,' . $role->id,
            'description'   => 'nullable|string|max:255',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(Role::allPermissions())),
        ]);

        $role->update([
            'name'        => $request->name,
            'description' => $request->description,
            'permissions' => $request->permissions ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Role \"{$role->name}\" updated successfully.",
        ]);
    }

    /**
     * Delete a role (system roles are protected).
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System roles cannot be deleted.',
            ], 422);
        }

        $assignedUsersCount = $role->users()->count();
        if ($assignedUsersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete — {$assignedUsersCount} staff member(s) are assigned this role. Re-assign them first.",
            ], 422);
        }

        $name = $role->name;
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => "Role \"{$name}\" deleted successfully.",
        ]);
    }
}
