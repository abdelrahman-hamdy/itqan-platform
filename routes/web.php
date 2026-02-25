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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
*/

// Liveness probes — public but rate-limited (used by load balancers)
Route::prefix('health')->middleware(['throttle:30,1'])->group(function () {
    Route::get('/live', [HealthCheckController::class, 'live']);
    Route::get('/ready', [HealthCheckController::class, 'ready']);
});

// Detailed health checks — restricted to admin users
Route::prefix('health')->middleware(['auth:web', 'throttle:10,1', 'role:super_admin,admin'])->group(function () {
    Route::get('/', [HealthCheckController::class, 'health']);
    Route::get('/db', [HealthCheckController::class, 'database']);
    Route::get('/redis', [HealthCheckController::class, 'redis']);
    Route::get('/queue', [HealthCheckController::class, 'queue']);
    Route::get('/storage', [HealthCheckController::class, 'storage']);
});

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
| URL patterns handled: /web-api/sessions/{id}/status,
|   /web-api/academic-sessions/{id}/status,
|   /web-api/quran-sessions/{id}/status,
|   /web-api/academic-sessions/{id}/attendance-status,
|   /web-api/quran-sessions/{id}/attendance-status,
|   /web-api/sessions/{id}/attendance-status
|   Plus subdomain-scoped: /notifications, /api/notifications/*,
|   /api/chat/unreadCount, /csrf-token, /custom-file-upload
|
| WHY ORDER MATTERS: The global /web-api/* routes (without a domain
| constraint) must be registered before subdomain route groups so that
| Laravel's router resolves them first. If subdomain groups were registered
| first, a request to /web-api/sessions/1/status on a subdomain host could
| potentially match a wildcard subdomain route before reaching the correct
| global handler. Loading this file first guarantees correct priority.
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
| URL patterns handled (all under subdomain domain group):
|   GET/POST /courses/{courseId}/lessons/{lessonId}/progress
|   POST     /courses/{courseId}/lessons/{lessonId}/complete
|   GET      /courses/{courseId}/lessons/{lessonId}
|   POST/DELETE /courses/{courseId}/lessons/{lessonId}/bookmark
|   POST/GET /courses/{courseId}/lessons/{lessonId}/notes
|   POST     /courses/{courseId}/lessons/{lessonId}/rate
|   GET      /courses/{courseId}/lessons/{lessonId}/transcript
|   GET      /courses/{courseId}/lessons/{lessonId}/materials
|   GET/OPTIONS /courses/{courseId}/lessons/{lessonId}/video
|   GET      /courses/{courseId}/progress
|   GET/POST /api/courses/{courseId}/... (auth-gated progress API)
|
| WHY ORDER MATTERS: All routes in this file use ->where() numeric
| constraints ([0-9]+) on {courseId} and {lessonId}, so they only match
| URLs where those segments are integers. However, they must still be
| registered BEFORE other course routes in student.php/teacher.php that
| use slug-based or catch-all course parameters — if those slug routes
| were registered first, a URL like /courses/42/lessons/7 could match a
| broader slug pattern before reaching the specific lesson route defined
| here, causing the wrong controller to handle the request.
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
| Help Center Routes
|--------------------------------------------------------------------------
| In-app help center (مركز المساعدة) for all authenticated user roles.
| Role-based article access is enforced in HelpCenterController.
*/

require __DIR__.'/web/help.php';

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
| Legal Pages (Public - No Auth Required)
|--------------------------------------------------------------------------
| Static pages required by app stores and regulations.
*/

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

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
