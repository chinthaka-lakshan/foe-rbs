<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ResourceController;


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