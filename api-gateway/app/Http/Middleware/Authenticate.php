<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json([
            'message' => 'Unauthenticated.'
        ], 401));
    }
}