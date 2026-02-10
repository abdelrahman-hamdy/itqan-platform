<?php

use App\Http\Controllers\Api\V1\Admin\SessionMonitoringController;
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
Route::middleware(function ($request, $next) {
    $user = $request->user();

    // Allow admin, super_admin, or supervisor
    if ($user && ($user->isAdmin() || $user->isSupervisor())) {
        return $next($request);
    }

    return response()->json([
        'success' => false,
        'message' => 'Access denied. Admin or Supervisor account required.',
        'error_code' => 'FORBIDDEN',
    ], 403);
})->group(function () {

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
