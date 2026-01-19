<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password/email', [AuthController::class, 'sendResetOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // 1. MASTER ADMIN ONLY: Permission Management
    Route::middleware('role:Master Admin')->group(function () {
        Route::post('/users/{id}/permissions', [UserPermissionController::class, 'updatePermissions']);
        Route::get('/users/permissions/overrides', [UserPermissionController::class, 'index']);
    });

    // 2. ADMIN & MASTER ADMIN: General Management
    Route::middleware('role:Master Admin,Admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::get('/admins', [UserController::class, 'getAdmins']);
    });

    // 3. SPECIFIC PERMISSION CHECK: Override-Aware Actions
    // Master Admin passes due to '*' ability. 
    // Admin only passes if 'user.delete' is in their token.
    Route::middleware('permission:user.delete')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
    Route::middleware(['auth:sanctum', 'permission:user.index'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });
});