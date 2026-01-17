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

        // Common routes (notifications, meetings, chat)
        require __DIR__.'/v1/common.php';
    });
});
