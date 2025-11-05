<?php

namespace App\Http\Controllers\Auth;

use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|min:8|same:password',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    // STEP 1: Send OTP to email
    public function sendResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        $otpExpiryMinutes = 5; // Shorter expiry for security

        // Store OTP in cache with prefixed key
        Cache::put(
            "password_reset_otp:{$user->email}",
            $otp,
            now()->addMinutes($otpExpiryMinutes)
        );

        // Send OTP via email
        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($user->name, $otp, $otpExpiryMinutes));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }

        return response()->json([
            'message' => 'OTP sent successfully to your email',
            'expires_in' => $otpExpiryMinutes * 60 // seconds
        ], 200);
    }

    // STEP 2: Verify OTP and issue reset token
    public function verifyResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        // Retrieve stored OTP
        $storedOtp = Cache::get("password_reset_otp:{$validated['email']}");

        if (!$storedOtp) {
            return response()->json([
                'message' => 'OTP expired or invalid'
            ], 400);
        }

        if ($storedOtp != $validated['otp']) {
            return response()->json([
                'message' => 'Incorrect OTP'
            ], 400);
        }

        // Generate secure reset token
        $resetToken = Str::random(64);
        $tokenExpiryMinutes = 15;

        // Store reset token with email
        Cache::put(
            "password_reset_token:{$resetToken}",
            $validated['email'],
            now()->addMinutes($tokenExpiryMinutes)
        );

        // Delete used OTP immediately
        Cache::forget("password_reset_otp:{$validated['email']}");

        return response()->json([
            'message' => 'OTP verified successfully',
            'reset_token' => $resetToken,
            'expires_in' => $tokenExpiryMinutes * 60 // seconds
        ], 200);
    }

    // STEP 3: Reset password with token
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'reset_token' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|min:8|same:new_password',
        ]);

        // Retrieve email from reset token
        $email = Cache::get("password_reset_token:{$validated['reset_token']}");

        if (!$email) {
            return response()->json([
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        // Find user and update password
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'password' => $validated['new_password'] // Auto-hashed by User model mutator
        ]);

        // Delete used reset token
        Cache::forget("password_reset_token:{$validated['reset_token']}");

        // Optional: Revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successful. Please login with your new password.'
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|min:8|same:new_password',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => [
                    'current_password' => ['The current password is incorrect.']
                ]
            ], 422);
        }

        $user->update([
            'password' => $validated['new_password']
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        // Delete all tokens (or use currentAccessToken()->delete() for current token only)
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
}
