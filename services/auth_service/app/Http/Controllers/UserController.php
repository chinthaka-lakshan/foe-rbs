<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // index
    public function index()
    {
        return User::with('roles')->get();
    }

    //store
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);
        $role = Role::where('name', 'User')->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        return response()->json($user, 201);
    }

    //update
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $user->id, 
            'password' => 'nullable|string|min:6',
            'status' => 'nullable|in:active,inactive',
            'role' => 'nullable|string|exists:roles,name',
        ]);
        $dataToUpdate = $validated;

        if (isset($dataToUpdate['password'])) {
            $dataToUpdate['password'] = Hash::make($dataToUpdate['password']);
        }

        unset($dataToUpdate['role']); 
        $user->update($dataToUpdate);

        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            $user->roles()->sync([$role->id]);
        }
        return response()->json($user->load('roles'));
    }

    // destroy
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }       
}
