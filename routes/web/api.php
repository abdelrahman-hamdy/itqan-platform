<?php

/*
|--------------------------------------------------------------------------
| Web API Routes
|--------------------------------------------------------------------------
| API endpoints for AJAX requests, session status, attendance, notifications.
| These routes use web middleware for session-based authentication.
*/

use App\Http\Controllers\Api\SessionStatusApiController;
use App\Http\Controllers\CustomFileUploadController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Session Status and Attendance APIs (Global Access - Priority Routes)
|--------------------------------------------------------------------------
| These routes handle session status and attendance for both academic and Quran sessions
| They are accessible globally (not bound to subdomains) for LiveKit interface compatibility
| IMPORTANT: These routes must be defined BEFORE subdomain routes to take priority
*/

// Session-type-specific status APIs (refactored to controller)
// Use /web-api/ prefix to avoid conflict with /api/ routes that use Sanctum auth
Route::middleware(['web'])->group(function () {
    Route::get('/web-api/academic-sessions/{session}/status', [SessionStatusApiController::class, 'academicSessionStatus'])
        ->name('api.academic-sessions.status');

    Route::get('/web-api/quran-sessions/{session}/status', [SessionStatusApiController::class, 'quranSessionStatus'])
        ->name('api.quran-sessions.status');

    // Session-type-specific attendance APIs (refactored to controller)
    Route::get('/web-api/academic-sessions/{session}/attendance-status', [SessionStatusApiController::class, 'academicAttendanceStatus'])
        ->name('api.academic-sessions.attendance-status');

    Route::get('/web-api/quran-sessions/{session}/attendance-status', [SessionStatusApiController::class, 'quranAttendanceStatus'])
        ->name('api.quran-sessions.attendance-status');

    // General session status API (supports all session types with polymorphic resolution)
    Route::get('/web-api/sessions/{session}/status', [SessionStatusApiController::class, 'generalSessionStatus'])
        ->name('web.api.sessions.status');

    Route::get('/web-api/sessions/{session}/attendance-status', [SessionStatusApiController::class, 'generalAttendanceStatus'])
        ->name('api.sessions.attendance-status');
});

/*
|--------------------------------------------------------------------------
| Debug Routes (Local/Testing Only)
|--------------------------------------------------------------------------
*/

if (app()->environment('local', 'testing')) {
    Route::get('/debug-api-test', function () {
        return response()->json([
            'success' => true,
            'message' => 'API endpoints are working!',
            'time' => now(),
            'routes_exist' => [
                'status' => \Illuminate\Support\Facades\Route::has('web.api.sessions.status'),
                'attendance' => \Illuminate\Support\Facades\Route::has('api.sessions.attendance-status'),
            ],
        ]);
    });
}

/*
|--------------------------------------------------------------------------
| Subdomain API Routes
|--------------------------------------------------------------------------
*/

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Notification API Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->group(function () {
        // Notifications page (available for all authenticated users)
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

        // Notification API endpoints
        Route::post('/api/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::post('/api/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
        Route::delete('/api/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Chat Unread Count (Web API for navigation badges)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->get('/api/chat/unreadCount', function () {
        $user = auth()->user();
        $conversations = \Wirechat\Wirechat\Models\Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('participantable_id', $user->id)
                ->where('participantable_type', $user->getMorphClass());
        })->get();

        $unreadCount = $conversations->sum(fn ($conv) => $conv->getUnreadCountFor($user));

        return response()->json(['unread_count' => $unreadCount]);
    })->name('chat.unread-count');

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Endpoint
    |--------------------------------------------------------------------------
    */

    Route::get('/csrf-token', function () {
        return response()->json([
            'token' => csrf_token(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Custom File Upload Route (Filament Components)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->post('/custom-file-upload', [CustomFileUploadController::class, 'upload'])->name('custom.file.upload');
});
