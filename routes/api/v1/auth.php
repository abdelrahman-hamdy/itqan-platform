<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Authentication Routes
|--------------------------------------------------------------------------
*/

// Public auth routes (require academy context only)
Route::middleware(['api.resolve.academy', 'api.academy.active'])->group(function () {

    // Login
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:10,1') // 10 attempts per minute
        ->name('api.v1.login');

    // Registration routes (require registration enabled)
    Route::middleware('api.academy.registration')->group(function () {
        Route::post('/register/student', [RegisterController::class, 'registerStudent'])
            ->name('api.v1.register.student');

        Route::post('/register/parent', [RegisterController::class, 'registerParent'])
            ->name('api.v1.register.parent');

        Route::post('/register/parent/verify-student', [RegisterController::class, 'verifyStudentCode'])
            ->name('api.v1.register.parent.verify-student');

        Route::post('/register/teacher/step1', [RegisterController::class, 'teacherStep1'])
            ->name('api.v1.register.teacher.step1');

        Route::post('/register/teacher/step2', [RegisterController::class, 'teacherStep2'])
            ->name('api.v1.register.teacher.step2');
    });

    // Password Reset
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:5,1') // 5 attempts per minute
        ->name('api.v1.forgot-password');

    Route::post('/verify-reset-token', [ForgotPasswordController::class, 'verifyToken'])
        ->middleware('throttle:10,1')
        ->name('api.v1.verify-reset-token');

    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('api.v1.reset-password');
});

// Authenticated auth routes
Route::middleware(['auth:sanctum', 'api.resolve.academy', 'api.academy.active', 'api.user.academy'])->group(function () {

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('api.v1.logout');

    // Token Management
    Route::prefix('token')->group(function () {
        Route::post('/refresh', [TokenController::class, 'refresh'])
            ->name('api.v1.token.refresh');

        Route::get('/validate', [TokenController::class, 'validateToken'])
            ->name('api.v1.token.validate');

        Route::delete('/revoke', [TokenController::class, 'revoke'])
            ->name('api.v1.token.revoke');

        Route::delete('/revoke-all', [TokenController::class, 'revokeAll'])
            ->name('api.v1.token.revoke-all');
    });

    // Get authenticated user info
    Route::get('/me', [LoginController::class, 'me'])
        ->name('api.v1.me');
});
