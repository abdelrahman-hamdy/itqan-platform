<?php

/*
|--------------------------------------------------------------------------
| Supervisor Education Routes
|--------------------------------------------------------------------------
| Routes for the supervisor education frontend.
| Prefix: /supervisor, Middleware: auth, role:supervisor,super_admin
*/

use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {
    Route::middleware(['auth', 'role:supervisor,super_admin'])->prefix('supervisor')->name('supervisor.')->group(function () {
        // Supervisor education routes will be added here
    });
});
