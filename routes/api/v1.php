<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Mobile API routes for Students, Parents, and Teachers.
| All routes are prefixed with /api/v1
|
*/

Route::prefix('v1')->middleware(['api.locale'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes (Academy Resolution Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['api.resolve.academy'])->group(function () {
        // Academy branding (public endpoint)
        Route::get('/academy/branding', [\App\Http\Controllers\Api\V1\Academy\BrandingController::class, 'show'])
            ->name('api.v1.academy.branding');

        // Server time (for client sync)
        Route::get('/server-time', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'timestamp' => now()->toISOString(),
                    'unix_timestamp' => now()->getTimestamp(),
                    'timezone' => config('app.timezone'),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'api_version' => 'v1',
                ],
            ]);
        })->name('api.v1.server-time');
    });

    /*
    |--------------------------------------------------------------------------
    | Public Browse Routes (Guest – No Authentication Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['api.resolve.academy'])->prefix('public')->group(function () {
        Route::get('/teachers/quran', [\App\Http\Controllers\Api\V1\Public\BrowseController::class, 'quranTeachers'])
            ->name('api.v1.public.teachers.quran');
        Route::get('/teachers/academic', [\App\Http\Controllers\Api\V1\Public\BrowseController::class, 'academicTeachers'])
            ->name('api.v1.public.teachers.academic');
        Route::get('/circles/quran', [\App\Http\Controllers\Api\V1\Public\BrowseController::class, 'quranCircles'])
            ->name('api.v1.public.circles.quran');
        Route::get('/courses/interactive', [\App\Http\Controllers\Api\V1\Public\BrowseController::class, 'interactiveCourses'])
            ->name('api.v1.public.courses.interactive');
        Route::get('/courses/recorded', [\App\Http\Controllers\Api\V1\Public\BrowseController::class, 'recordedCourses'])
            ->name('api.v1.public.courses.recorded');
    });

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    require __DIR__.'/v1/auth.php';

    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'api.resolve.academy',
        'api.academy.active',
        'api.user.academy',
    ])->group(function () {

        // Student routes
        Route::prefix('student')->group(function () {
            require __DIR__.'/v1/student.php';
        });

        // Parent routes
        Route::prefix('parent')->group(function () {
            require __DIR__.'/v1/parent.php';
        });

        // Teacher routes
        Route::prefix('teacher')->group(function () {
            require __DIR__.'/v1/teacher.php';
        });

        // Admin routes (SuperAdmin, Admin, Supervisor)
        Route::prefix('admin')->group(function () {
            require __DIR__.'/v1/admin.php';
        });

        // Supervisor-specific routes
        Route::prefix('supervisor')->group(function () {
            require __DIR__.'/v1/supervisor.php';
        });

        // Common routes (notifications, meetings, chat)
        require __DIR__.'/v1/common.php';
    });
});
