<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\BookingItemController;


// Resource Service Category Routes
Route::post('/categories', [CategoryController::class, 'store']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);

// Resource Service Resource Routes
Route::post('/resources', [ResourceController::class, 'store']);
Route::get('/resources', [ResourceController::class, 'index']);
Route::get('/resources/{id}', [ResourceController::class, 'show']);
Route::put('/resources/{id}', [ResourceController::class, 'update']);
Route::post('/resources/{id}', [ResourceController::class, 'update']);
Route::delete('/resources/{id}', [ResourceController::class, 'destroy']);

// Booking Item Routes
Route::get('/booking-items', [BookingItemController::class, 'index']);
Route::get('/booking-items/available', [BookingItemController::class, 'available']);
Route::post('/booking-items', [BookingItemController::class, 'store']);
Route::get('/booking-items/{id}', [BookingItemController::class, 'show']);
Route::put('/booking-items/{id}', [BookingItemController::class, 'update']);
Route::delete('/booking-items/{id}', [BookingItemController::class, 'destroy']);