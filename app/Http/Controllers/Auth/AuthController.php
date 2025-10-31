<?php

namespace App\Http\Controllers\Auth;

use App\Mail\PasswordResetOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function logout(Request $request)
    {
        // Delete all tokens (or use currentAccessToken()->delete() for current token only)
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
}

// namespace App\Http\Controllers\Auth;

// use App\Mail\PasswordResetOtpMail;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\Mail;

// use App\Http\Controllers\Controller;
// use App\Models\User;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;

// class AuthController extends Controller
// {
//     public function register(Request $request)
//     {
//         $request->validate([
//             'name' => 'required|string|max:255',
//             'email' => 'required|string|email|max:255|unique:users',
//             'password' => 'required|string|min:8',
//             'password_confirmation' => 'required|string|min:8|same:password',
//         ]);

//         $user = User::create([
//             'name' => $request->input("name"),
//             'email' => $request->input("email"),
//             'password' => $request->input("password"),
//         ]);

//         return response()->json([
//             'message' => 'User registered successfully',
//             'user' => $user,
//         ], 201);
//     }
//     public function login(Request $request)
//     {
//         $request->validate([
//             'email' => 'required|string|email|exists:users,email',
//             'password' => 'required|string',
//         ]);

//         $user = User::where('email', $request->input("email"))->first();

//         if (!$user || !Hash::check($request->input("password"), $user->password)) {
//             return response()->json([
//                 'message' => 'Invalid credentials',
//             ], 401);
//         }
//         $token = $user->createToken('authToken')->plainTextToken;
//         return response()->json([
//             'message' => 'User logged in successfully',
//             'token' => $token,
//             'user' => [
//                 'id' => $user->id,
//                 'name' => $user->name,
//                 'email' => $user->email,
//             ],
//         ], 200);
//     }
//     public function sendResetOtp(Request $request)
//     {
//         $request->validate(['email' => 'required|email']);
//         $user = User::where('email', $request->input("email"))->first();
//         if (!$user) {
//             return response()->json(['message' => 'User not found'], 404);
//         }
//         $otp = rand(100000, 999999);
//         $expiryMinutes = 10;
//         // Store OTP temporarily in cache or database
//         Cache::put('password_reset_otp_' . $user->email, $otp, now()->addMinutes($expiryMinutes));
//         // Send email
//         Mail::to($user->email)->send(new PasswordResetOtpMail($user->name, $otp, $expiryMinutes));
//         return response()->json(['message' => 'OTP sent successfully']);
//     }
//     public function verifyOtpAndResetPassword(Request $request)
//     {
//         $request->validate([
//             'email' => 'required|email',
//             'otp' => 'required|digits:6',
//             'password' => 'required|min:8',
//             'password_confirmation' => 'required|min:8|same:password',
//         ]);

//         $storedOtp = Cache::get('password_reset_otp_' . $request->input("email"));

//         if (!$storedOtp) {
//             return response()->json(['message' => 'OTP expired or invalid'], 400);
//         }

//         if ($storedOtp != $request->otp) {
//             return response()->json(['message' => 'Incorrect OTP'], 400);
//         }

//         $user = User::where('email', $request->input("email"))->first();
//         $user->update(['password' => bcrypt($request->input("password"))]);

//         Cache::forget('password_reset_otp_' . $request->input("email"));

//         return response()->json(['message' => 'Password reset successful']);
//     }
//     public function me(Request $request)
//     {
//         return response()->json([
//             'user' => Auth::user(),
//         ], 200);
//     }
//     public function chnagePassword(Request $request)
//     {
//         $request->validate([
//             'password' => 'required|string|min:8',
//             'password_confirmation' => 'required|string|min:8|same:password',
//         ]);

//         $user = $request->user();
//         $user->password = $request->input("password");
//         $user->save();

//         return response()->json([
//             'message' => 'Password changed successfully',
//         ], 200);
//     }
//     public function logout(Request $request)
//     {
//         $request->user()->tokens()->delete();
//         return response()->json([
//             'success' => true,
//             'message' => 'Logout successful.',
//         ]);
//     }
// }
