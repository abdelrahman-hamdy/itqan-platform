<?php

/*
|--------------------------------------------------------------------------
| Admin Education Routes
|--------------------------------------------------------------------------
| Routes for the admin education management frontend.
| Prefix: /manage, Middleware: auth, role:admin,super_admin
*/

use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {
    Route::middleware(['auth', 'role:admin,super_admin'])->prefix('manage')->name('manage.')->group(function () {
        // Admin education routes will be added here
    });
});
