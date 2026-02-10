<?php

use App\Http\Controllers\Api\V1\Admin\SessionMonitoringController;
use App\Http\Middleware\Api\EnsureAdminOrSupervisor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Admin Routes
|--------------------------------------------------------------------------
|
| Routes for SuperAdmin, Admin, and Supervisor roles
| Requires: auth:sanctum, api.resolve.academy, api.academy.active, api.user.academy
|
*/

// Routes accessible by Admin, SuperAdmin, or Supervisor
Route::middleware(EnsureAdminOrSupervisor::class)->group(function () {

    // Session Monitoring
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionMonitoringController::class, 'index'])
            ->name('api.v1.admin.sessions.index');

        Route::get('/{sessionType}/{sessionId}', [SessionMonitoringController::class, 'show'])
            ->where('sessionType', 'quran|academic|interactive')
            ->name('api.v1.admin.sessions.show');
    });
});

// SuperAdmin-only routes (for future use)
Route::middleware('api.is.super-admin')->group(function () {
    // Future: system-wide operations
});
