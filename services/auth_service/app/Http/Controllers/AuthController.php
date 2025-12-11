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
    // login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Check if user is active
            if ($user->status !== 'active') {
                return response()->json(['message' => 'Account is not active'], 403);
            }

            $token = $user->createToken('auth-token')->plainTextToken;
            
            return response()->json([
                'user' => $user->load('roles'),
                'roles' => $user->roles->pluck('name'),
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
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
