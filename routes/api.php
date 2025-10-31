<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\UserController as AuthUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication routes (public)
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/send-reset-otp', 'sendResetOtp');
    Route::post('/verify-otp-and-reset-password', 'verifyOtpAndResetPassword');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth-related protected routes
    Route::controller(AuthController::class)->group(function () {
        Route::get('/me', 'me');
        Route::post('/change-password', 'changePassword');
        Route::post('/logout', 'logout');
    });

    // User resource routes
    Route::apiResource('users', AuthUserController::class);
});

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
