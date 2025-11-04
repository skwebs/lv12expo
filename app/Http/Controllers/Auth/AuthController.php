<?php

namespace App\Http\Controllers\Auth;

use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        // if (!$user || !Hash::check($credentials['password'], $user->password)) {
        //     throw ValidationException::withMessages([
        //         'email' => ['The provided credentials are incorrect.'],
        //     ]);
        // }

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

    public function sendResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $validated['email'])->first();

        $otp = rand(100000, 999999);
        $expiryMinutes = 10;

        // Store OTP in cache
        Cache::put(
            'password_reset_otp_' . $user->email,
            $otp,
            now()->addMinutes($expiryMinutes)
        );

        // Send email
        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($user->name, $otp, $expiryMinutes));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }

        return response()->json([
            'message' => 'OTP sent successfully to your email'
        ], 200);
    }


    public function verifyOtpAndResetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|min:8|same:new_password',
        ]);

        $storedOtp = Cache::get('password_reset_otp_' . $validated['email']);

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

        $user = User::where('email', $validated['email'])->first();
        $user->update([
            // 'password' => Hash::make($validated['password'])
            'password' => $validated['new_password']
        ]);

        // Clear OTP from cache
        Cache::forget('password_reset_otp_' . $validated['email']);

        return response()->json([
            'message' => 'Password reset successful'
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
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
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
        $user = $request->user(); // Get the authenticated user

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
