<?php

use App\Http\Controllers\Api\V1\Admin\MeetingObserverController;
use App\Http\Controllers\Api\V1\Admin\SessionMonitoringController;
use App\Http\Controllers\Api\V1\Admin\TestFixtureController;
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

    // LiveKit Observer Token (for active meetings)
    Route::get('meetings/{sessionType}/{sessionId}/token', [MeetingObserverController::class, 'getObserverToken'])
        ->where('sessionType', 'quran|academic|interactive')
        ->name('api.v1.admin.meetings.observer-token');

    // Test fixtures for mobile integration_test suite. Do not use these
    // endpoints from production clients — they exist solely so the regression
    // anchor in `integration_test/student_detailed/session_detail_test.dart`
    // can seed a `suspended` session that is otherwise unreachable via the
    // normal subscription/scheduling flow.
    Route::prefix('test-fixtures')->group(function () {
        Route::post('suspended-session', [TestFixtureController::class, 'createSuspendedSession'])
            ->name('api.v1.admin.test-fixtures.suspended-session');
    });
});

// SuperAdmin-only routes (for future use)
Route::middleware('api.is.super-admin')->group(function () {
    // Future: system-wide operations
});
