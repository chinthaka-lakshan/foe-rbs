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
        // Validate the simple format used in your Postman screenshot
        $validated = $request->validate([
            'permission_slug' => 'required|string',
            'is_allowed' => 'required|boolean'
        ]);

        // Update or Create the override
        UserPermissionOverride::updateOrCreate(
            ['user_id' => $userId, 'permission_slug' => $validated['permission_slug']],
            ['is_allowed' => $validated['is_allowed']]
        );

        return response()->json(['message' => 'User permission override updated successfully']);
    }
}
