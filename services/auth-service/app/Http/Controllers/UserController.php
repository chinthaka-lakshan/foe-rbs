<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //store
    public function store(Request $request)
    {
        //Validation logic
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        //User creation logic (status defaults to 'active')
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        //Default Role Assignment
        $role = Role::where('name', 'User')->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        return response()->json($user, 201);
    }

    //update
    public function update(Request $request, User $user)
    {
        // Validation logic
        $validated = $request->validate([
            'status' => 'nullable|in:active,inactive',
            'role' => 'nullable|string|exists:roles,name',
        ]);

        if (isset($validated['status'])) {
            $user->update(['status' => $validated['status']]);
        }

        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            $user->roles()->sync([$role->id]);
        }

        return response()->json($user->load('roles'));
    }
}
