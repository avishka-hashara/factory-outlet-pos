<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    /**
     * Display a listing of all staff members
     */
    public function index()
    {
        $staff = User::with('permissionRole')->get();
        $roles = \App\Models\Role::orderBy('name')->get();
        return view('staff.index', compact('staff', 'roles'));
    }

    /**
     * Store a newly created staff member
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['password'] = bcrypt($validated['password']);

            User::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create staff member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified staff member
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found'
            ], 404);
        }
    }

    /**
     * Update the specified staff member
     */
    public function update($id, UpdateStaffRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $validated = $request->validated();
            
            // Only update password if provided
            if (!empty($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the specified staff member
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Use canBeDeleted method from HasPermissions trait
            if (!$user->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the only admin user'
                ], 422);
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update staff member status
     */
    public function updateStatus($id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:Active,Inactive',
            ]);

            $user = User::findOrFail($id);
            $user->status = $request->status;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Staff status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff status: ' . $e->getMessage()
            ], 500);
        }
    }
}
