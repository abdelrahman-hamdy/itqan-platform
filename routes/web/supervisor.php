<?php

/*
|--------------------------------------------------------------------------
| Supervisor & SuperAdmin Frontend Routes
|--------------------------------------------------------------------------
| Sessions monitoring page for supervisors and super admins to observe
| active meetings from the frontend.
*/

use App\Http\Controllers\SessionsMonitoringController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    Route::middleware(['auth'])->group(function () {
        // Sessions monitoring page (supervisor & super_admin)
        Route::get('/sessions-monitoring', [SessionsMonitoringController::class, 'index'])
            ->name('sessions.monitoring');
    });
});
