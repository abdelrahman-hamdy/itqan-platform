<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file serves as the main router for the Itqan Platform.
| Routes are organized into domain-specific files for better maintainability.
|
| Route Files:
| - web/public.php    : Platform landing, academy homepage, public browsing
| - web/student.php   : Student profile, sessions, homework, quizzes, certificates
| - web/teacher.php   : Teacher session management, reports, homework grading
| - web/parent.php    : Parent portal, children management, reports
| - web/payments.php  : Payment processing, history, webhooks
| - web/meetings.php  : LiveKit integration, webhooks, recording API
| - web/lessons.php   : Course lessons, progress tracking, bookmarks
| - web/api.php       : Web API endpoints for AJAX requests
| - web/chat.php      : WireChat integration
| - web/dev.php       : Development utilities (local only)
| - auth.php          : Authentication routes (login, register, password reset)
|
*/

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes (No Middleware - Must be accessible by load balancers)
|--------------------------------------------------------------------------
*/

Route::prefix('health')->group(function () {
    Route::get('/', [HealthCheckController::class, 'health']);
    Route::get('/live', [HealthCheckController::class, 'live']);
    Route::get('/ready', [HealthCheckController::class, 'ready']);
    Route::get('/db', [HealthCheckController::class, 'database']);
    Route::get('/redis', [HealthCheckController::class, 'redis']);
    Route::get('/queue', [HealthCheckController::class, 'queue']);
    Route::get('/storage', [HealthCheckController::class, 'storage']);
});

/*
|--------------------------------------------------------------------------
| Broadcasting Authentication
|--------------------------------------------------------------------------
*/

Broadcast::routes(['middleware' => ['web', 'auth']]);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Includes login, registration, and password reset for all user roles.
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Session Status and Attendance APIs (Global - Priority Routes)
|--------------------------------------------------------------------------
| These routes must be loaded BEFORE subdomain routes to ensure they take
| priority. They handle session status and attendance for LiveKit interface.
*/

require __DIR__.'/web/api.php';

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Platform landing page, academy homepage, static pages, and public
| browsing of teachers, courses, and circles.
*/

require __DIR__.'/web/public.php';

/*
|--------------------------------------------------------------------------
| Lesson & Course Learning Routes
|--------------------------------------------------------------------------
| IMPORTANT: Must be loaded BEFORE other course routes to avoid conflicts.
*/

require __DIR__.'/web/lessons.php';

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
| Student profile, subscriptions, sessions, homework, quizzes, certificates.
*/

require __DIR__.'/web/student.php';

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
| Teacher session management, homework grading, reports, student management.
*/

require __DIR__.'/web/teacher.php';

/*
|--------------------------------------------------------------------------
| Parent Routes
|--------------------------------------------------------------------------
| Parent portal for viewing children's data, subscriptions, and reports.
*/

require __DIR__.'/web/parent.php';

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
| Payment processing, history, and gateway integration.
*/

require __DIR__.'/web/payments.php';

/*
|--------------------------------------------------------------------------
| Meeting Routes
|--------------------------------------------------------------------------
| LiveKit video meeting integration, webhooks, and recording management.
*/

require __DIR__.'/web/meetings.php';

/*
|--------------------------------------------------------------------------
| Chat Routes
|--------------------------------------------------------------------------
| WireChat integration for real-time messaging.
*/

require __DIR__.'/web/chat.php';

/*
|--------------------------------------------------------------------------
| Supervisor & SuperAdmin Frontend Routes
|--------------------------------------------------------------------------
| Sessions monitoring page for observing active meetings.
*/

require __DIR__.'/web/supervisor.php';

/*
|--------------------------------------------------------------------------
| Mobile Purchase Redirect Routes
|--------------------------------------------------------------------------
| Handles redirects from mobile app to web checkout pages.
| Uses Sanctum token authentication with web-purchase ability.
*/

Route::get('/mobile-purchase/{type}/{id}', [\App\Http\Controllers\WebPurchaseController::class, 'mobileRedirect'])
    ->name('mobile.purchase.redirect');

/*
|--------------------------------------------------------------------------
| Development Routes (Local Only)
|--------------------------------------------------------------------------
| Certificate previews and other development utilities.
*/

require __DIR__.'/web/dev.php';
