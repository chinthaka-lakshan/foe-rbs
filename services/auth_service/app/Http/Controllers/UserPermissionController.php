<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPermissionOverride;
use App\Models\User;

class UserPermissionController extends Controller
{
    // List all user permission overrides
    public function index()
    {
        return response()->json(UserPermissionOverride::with('user')->get());
    }
    // Update user permissions with overrides
    public function updatePermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.slug' => 'required|string',
            'permissions.*.status' => 'required|in:allow,deny,default'
        ]);

        foreach ($request->permissions as $perm) {
            if ($perm['status'] === 'default') {
                // Remove override to return to Role-based logic
                UserPermissionOverride::where('user_id', $userId)
                    ->where('permission_slug', $perm['slug'])
                    ->delete();
            } else {
                // Set explicit Grant or Deny
                UserPermissionOverride::updateOrCreate(
                    ['user_id' => $userId, 'permission_slug' => $perm['slug']],
                    ['is_granted' => $perm['status'] === 'allow']
                );
            }
        }

        return response()->json(['message' => 'Permissions updated successfully']);
    }
}
