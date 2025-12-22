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
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::post('/users', [UserController::class, 'store']);
Route::post('/forgot-password/email', [AuthController::class, 'sendResetOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum', 'role:Master Admin,Admin'])->group(function () {
    // Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/admins', [UserController::class, 'getAdmins']);
});
Route::middleware(['auth:sanctum', 'role:Master Admin'])->group(function () {
    Route::post('/users/{id}/permissions', [UserPermissionController::class, 'updatePermissions']);
    Route::get('/users/permissions/overrides', [UserPermissionController::class, 'index']);
});