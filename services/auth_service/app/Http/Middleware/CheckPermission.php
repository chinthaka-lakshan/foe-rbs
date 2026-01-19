<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission)
        {
            // $request->user() gets the user from the Sanctum token
            // tokenCan() checks the "abilities" we put in the token during login
            if (!$request->user() || !$request->user()->tokenCan($permission)) {
                return response()->json([
                    'message' => "Access Denied. You do not have the permission: {$permission}"
                ], 403);
            }

            return $next($request);
        }
}
