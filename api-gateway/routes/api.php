<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

//--------------------------------------------------------------------------
// API Routes
//--------------------------------------------------------------------------
//User login route
Route::post('/login', function (Request $request) {
    try {
        $response = Http::timeout(30)->post('http://auth_service/api/login', $request->all());
        
        return $response->json();
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Cannot connect to authentication service',
            'error' => $e->getMessage()
        ], 503);
    }
});

//User registration route
Route::post('/users', function (Request $request) {
    try {
        $response = Http::timeout(30)->withToken($request->bearerToken())
            ->post('http://auth_service/api/users', $request->all());
        
        return $response->json();
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Cannot connect to authentication service',
            'error' => $e->getMessage()
        ], 503);
    }
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user details
    Route::get('/users', function (Request $request) {
        $response = Http::withToken($request->bearerToken())
            ->get('http://auth_service/api/users');
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'Failed to get user'], $response->status());
    });

    // User logout route
    Route::post('/logout', function (Request $request) {
        $response = Http::withToken($request->bearerToken())
            ->post('http://auth_service/api/logout');
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'Logout failed'], $response->status());
    });
    // Update user route (Admin, Master Admin)
    Route::put('/users/{user}', function (Request $request, $user) {
        $response = Http::withToken($request->bearerToken())
            ->put("http://auth_service/api/users/{$user}", $request->all());
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'User update failed'], $response->status());
    });
    // Delete user route (Admin, Master Admin)
    Route::delete('/users/{user}', function (Request $request, $user) {
        $response = Http::withToken($request->bearerToken())
            ->delete("http://auth_service/api/users/{$user}");
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'User deletion failed'], $response->status());
    });
});