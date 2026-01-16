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

    // Login - stricter rate limiting for security
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1') // 5 attempts per minute (reduced for brute force protection)
        ->name('api.v1.login');

    // Registration routes (require registration enabled)
    // Rate limited to prevent spam account creation
    Route::middleware('api.academy.registration')->group(function () {
        Route::post('/register/student', [RegisterController::class, 'registerStudent'])
            ->middleware('throttle:5,1') // 5 attempts per minute
            ->name('api.v1.register.student');

        Route::post('/register/parent', [RegisterController::class, 'registerParent'])
            ->middleware('throttle:5,1') // 5 attempts per minute
            ->name('api.v1.register.parent');

        Route::post('/register/parent/verify-student', [RegisterController::class, 'verifyStudentCode'])
            ->middleware('throttle:10,1') // 10 attempts per minute (more lenient for verification)
            ->name('api.v1.register.parent.verify-student');

        Route::post('/register/teacher/step1', [RegisterController::class, 'teacherStep1'])
            ->middleware('throttle:3,1') // 3 attempts per minute (stricter for teachers)
            ->name('api.v1.register.teacher.step1');

        Route::post('/register/teacher/step2', [RegisterController::class, 'teacherStep2'])
            ->middleware('throttle:3,1') // 3 attempts per minute
            ->name('api.v1.register.teacher.step2');

        // Dynamic form options for registration
        Route::get('/register/subjects', [RegisterController::class, 'getSubjects'])
            ->middleware('throttle:30,1') // More lenient for read-only data
            ->name('api.v1.register.subjects');

        Route::get('/register/grade-levels', [RegisterController::class, 'getGradeLevels'])
            ->middleware('throttle:30,1')
            ->name('api.v1.register.grade-levels');
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

    // Email Verification
    Route::post('/email/resend', [LoginController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('api.v1.email.resend');

    Route::get('/email/verification-status', [LoginController::class, 'verificationStatus'])
        ->name('api.v1.email.status');
});
