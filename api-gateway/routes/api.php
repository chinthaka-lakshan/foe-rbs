<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// api-gateway/routes/api.php (Simplified, assuming all internal /users/* endpoints use this)

Route::post('/login', function (Request $request) {
    return Http::post('http://auth_service/api/login', $request->all())->json();
});
Route::post('/users', function (Request $request) {
    return Http::post('http://auth_service/api/users', $request->all())->json();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
    });
    Route::put('/users/{user}', function (Request $request, $user) {
        return Http::withToken($request->bearerToken())
            ->put('http://auth_service/api/users/' . $user, $request->all())
            ->json();
    });
});
