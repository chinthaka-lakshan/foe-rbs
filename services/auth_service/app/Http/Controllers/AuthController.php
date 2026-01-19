<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOtpMail;

class AuthController extends Controller
{

public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // 1. Find user and load the roles relationship
    $user = User::with('roles')->where('email', $request->email)->first();

    if (!$user || !\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // 2. Calculate final permissions (Role + Overrides) for the token abilities
    $permissions = $user->getAllPermissions();

    // 3. Create the role names array for the response
    $roleNames = $user->roles->pluck('name');

    // 4. Issue Token with 'Abilities'
    $token = $user->createToken('auth_token', $permissions)->plainTextToken;

    // 5. Return the exact structure requested
    return response()->json([
        'user' => $user,
        'roles' => $roleNames,
        'token' => $token
    ]);
}

    // sendResetOtp
    public function sendResetOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $email = $request->email;
        $token = strval(rand(100000, 999999)); 

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );
        
        // 🛑 CRITICAL FIX: Send the actual email
        Mail::to($email)->send(new ResetPasswordOtpMail($token));

        // \Log::info(...) can be deleted or kept for debugging

        return response()->json([
            'message' => 'OTP sent successfully. Check your email.'
        ]);
    }


    // verifyOtp
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        $resetRecord = DB::table('password_resets')
            ->where('email', $validated['email'])
            ->first();
        if (!$resetRecord || 
            !Hash::check($validated['otp'], $resetRecord->token) ||
            \Carbon\Carbon::parse($resetRecord->created_at)->addMinutes(15)->isPast()
        ) {
            return response()->json([
                'message' => 'Invalid or expired OTP.'
            ], 401);
        }

        return response()->json([
            'message' => 'OTP verified successfully. You can now set your new password.'
        ]);
    }

    // resetPassword
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $resetRecord = DB::table('password_resets')
            ->where('email', $validated['email'])
            ->first();

        if (!$resetRecord || !Hash::check($validated['otp'], $resetRecord->token)) {
            return response()->json(['message' => 'Invalid verification token.'], 401);
        }
        $user = User::where('email', $validated['email'])->first();
        $user->password = Hash::make($validated['password']);
        $user->save();
        DB::table('password_resets')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }

    // logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
