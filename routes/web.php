<?php

use App\Enums\SessionStatus;
use App\Http\Controllers\AcademyHomepageController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecordedCourseController;
use App\Http\Controllers\StudentDashboardController;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Include authentication routes
require __DIR__.'/auth.php';

// Broadcasting authentication for private channels
Broadcast::routes(['middleware' => ['web', 'auth']]);

/*
|--------------------------------------------------------------------------
| Google OAuth Routes (Local Development)
|--------------------------------------------------------------------------
| These routes handle Google OAuth for local development (localhost:8000)
| For production, the subdomain-based routes below are used instead.
*/

if (config('app.env') === 'local') {
    // Google OAuth for teachers (local development only)

    // Routes that require authentication
    Route::middleware(['auth'])->group(function () {
        Route::get('/google/auth', [App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('google.auth.local');
        Route::post('/google/disconnect', [App\Http\Controllers\GoogleAuthController::class, 'disconnect'])->name('google.disconnect.local');
        Route::get('/google/status', [App\Http\Controllers\GoogleAuthController::class, 'status'])->name('google.status.local');
        Route::get('/google/test', [App\Http\Controllers\GoogleAuthController::class, 'test'])->name('google.test.local');
    });

    // Callback route should NOT require authentication (Google redirects here)
    Route::get('/google/callback', [App\Http\Controllers\GoogleAuthController::class, 'callback'])->name('google.callback.local');
}

// LiveKit routes for teacher controls (tenant-aware)
Route::prefix('livekit')->middleware(['auth'])->group(function () {
    // Basic participant endpoints available to authenticated users
    Route::get('participants', [App\Http\Controllers\LiveKitController::class, 'getParticipants']);

    // Teacher-only participant control endpoints with detailed debugging
    Route::middleware(['control-participants'])->group(function () {
        Route::post('participants/mute', [App\Http\Controllers\LiveKitController::class, 'muteParticipant']);
        Route::post('participants/mute-all-students', [App\Http\Controllers\LiveKitController::class, 'muteAllStudents']);
        Route::get('rooms/{room_name}/participants', [App\Http\Controllers\LiveKitController::class, 'getRoomParticipants']);
    });

    // Temporary debug endpoint to test middleware flow
    Route::post('test-auth', function (Request $request) {
        \Log::info('LiveKit test-auth endpoint hit', [
            'auth_check' => auth()->check(),
            'user_id' => auth()->check() ? auth()->user()->id : null,
            'user_type' => auth()->check() ? auth()->user()->user_type : null,
            'session_id' => $request->session()->getId(),
            'csrf_token' => $request->header('X-CSRF-TOKEN'),
            'headers' => $request->headers->all(),
            'academy' => $request->get('academy'),
        ]);

        return response()->json([
            'success' => true,
            'authenticated' => auth()->check(),
            'user_type' => auth()->check() ? auth()->user()->user_type : null,
            'can_control' => auth()->check() && in_array(auth()->user()->user_type, ['quran_teacher', 'academic_teacher', 'admin', 'super_admin']),
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Session Status and Attendance APIs (Global Access - Priority Routes)
|--------------------------------------------------------------------------
| These routes handle session status and attendance for both academic and Quran sessions
| They are accessible globally (not bound to subdomains) for LiveKit interface compatibility
| IMPORTANT: These routes must be defined BEFORE subdomain routes to take priority
*/

// EMERGENCY DEBUG: Test route to show EXACTLY what sessions exist and what our logic does
Route::get('/api/sessions/{session}/debug-resolution', function (Request $request, $session) {
    // Check what exists in both tables
    $academicSession = \App\Models\AcademicSession::find($session);
    $quranSession = \App\Models\QuranSession::find($session);

    // Test our current resolution logic
    $resolvedSession = \App\Models\AcademicSession::find($session);
    if (! $resolvedSession) {
        $resolvedSession = \App\Models\QuranSession::find($session);
    }

    return response()->json([
        'success' => true,
        'message' => 'DEBUG: Session Resolution Analysis',
        'session_id' => $session,
        'academic_session_exists' => $academicSession ? true : false,
        'academic_session_data' => $academicSession ? [
            'id' => $academicSession->id,
            'status' => $academicSession->getRawOriginal('status'),
            'scheduled_at' => $academicSession->scheduled_at,
            'class' => get_class($academicSession),
        ] : null,
        'quran_session_exists' => $quranSession ? true : false,
        'quran_session_data' => $quranSession ? [
            'id' => $quranSession->id,
            'status' => $quranSession->getRawOriginal('status'),
            'scheduled_at' => $quranSession->scheduled_at,
            'session_type' => $quranSession->session_type,
            'class' => get_class($quranSession),
        ] : null,
        'current_logic_resolves_to' => $resolvedSession ? [
            'id' => $resolvedSession->id,
            'status' => $resolvedSession->getRawOriginal('status'),
            'class' => get_class($resolvedSession),
            'is_academic' => $resolvedSession instanceof \App\Models\AcademicSession,
            'is_quran' => $resolvedSession instanceof \App\Models\QuranSession,
        ] : null,
        'PROBLEM' => 'If both sessions exist with same ID, Academic is ALWAYS chosen!',
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'timestamp' => now(),
    ]);
});

// Session-type-specific status APIs (these are clearer and avoid conflicts)
Route::get('/api/academic-sessions/{session}/status', function (Request $request, $session) {
    if (! auth()->check()) {
        return response()->json([
            'message' => 'يجب تسجيل الدخول لعرض حالة الجلسة',
            'status' => 'unauthenticated',
            'can_join' => false,
            'button_text' => 'يجب تسجيل الدخول',
            'button_class' => 'bg-gray-400 cursor-not-allowed',
        ], 401);
    }

    $user = $request->user();
    $userType = $user->hasRole('academic_teacher') ? 'academic_teacher' : 'student';
    $session = \App\Models\AcademicSession::findOrFail($session);

    // Academic sessions use default configuration
    $circle = null;
    $preparationMinutes = 15; // Default for academic sessions
    $endingBufferMinutes = 5;

    // ... rest of the academic session logic
    $now = now();
    $canJoinMeeting = false;
    $message = '';
    $buttonText = '';
    $buttonClass = '';

    // Check if user can join meeting based on timing and status
    if ($userType === 'academic_teacher' && in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING,
        App\Enums\SessionStatus::SCHEDULED,
    ])) {
        if ($session->scheduled_at) { // NULL check
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);
            if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
                $canJoinMeeting = true;
            }
        }
    } elseif ($userType === 'student' && in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING,
        App\Enums\SessionStatus::SCHEDULED,
    ])) {
        if ($session->scheduled_at) { // NULL check
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);
            if ($now->lt($sessionEnd)) {
                $canJoinMeeting = true;
            }
        }
    }

    // Determine message and button state based on session status
    $statusValue = is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : $session->status;

    switch ($session->status) {
        case App\Enums\SessionStatus::READY:
            $message = 'الجلسة جاهزة';
            $buttonText = 'غير متاح';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case App\Enums\SessionStatus::ONGOING:
            $message = 'الجلسة جارية حالياً';
            $buttonText = $canJoinMeeting ? 'انضم للجلسة' : 'غير متاح';
            $buttonClass = $canJoinMeeting ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-400 cursor-not-allowed';
            break;

        case App\Enums\SessionStatus::COMPLETED:
            $message = 'تم إنهاء الجلسة';
            $buttonText = 'الجلسة منتهية';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case App\Enums\SessionStatus::CANCELLED:
            $message = 'تم إلغاء الجلسة';
            $buttonText = 'الجلسة ملغية';
            $buttonClass = 'bg-red-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case App\Enums\SessionStatus::SCHEDULED:
            if ($canJoinMeeting) {
                $message = 'يمكنك الانضمام للجلسة الآن';
                $buttonText = 'انضم للجلسة';
                $buttonClass = 'bg-green-500 hover:bg-green-600';
            } else {
                if ($session->scheduled_at) { // NULL check
                    $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                    $timeData = formatTimeRemaining($preparationTime);
                    $message = ! $timeData['is_past'] ? "الجلسة ستكون متاحة خلال {$timeData['formatted']}" : 'الجلسة ستكون متاحة قريباً';
                } else {
                    $message = 'الجلسة محجوزة ولكن لم يتم تحديد موعد';
                }
                $buttonText = 'في انتظار الجلسة';
                $buttonClass = 'bg-blue-400 cursor-not-allowed';
                $canJoinMeeting = false;
            }
            break;

        case App\Enums\SessionStatus::UNSCHEDULED:
            $message = 'الجلسة غير مجدولة بعد';
            $buttonText = 'في انتظار الجدولة';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        default:
            $message = 'حالة غير معروفة';
            $buttonText = 'غير متاح';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
    }

    return response()->json([
        'status' => $statusValue,
        'message' => $message,
        'button_text' => $buttonText,
        'button_class' => $buttonClass,
        'can_join' => $canJoinMeeting,
        'session_type' => 'academic',
    ]);
})->name('api.academic-sessions.status');

Route::get('/api/quran-sessions/{session}/status', function (Request $request, $session) {
    if (! auth()->check()) {
        return response()->json([
            'message' => 'يجب تسجيل الدخول لعرض حالة الجلسة',
            'status' => 'unauthenticated',
            'can_join' => false,
            'button_text' => 'يجب تسجيل الدخول',
            'button_class' => 'bg-gray-400 cursor-not-allowed',
        ], 401);
    }

    $user = $request->user();
    $userType = $user->hasRole('quran_teacher') ? 'quran_teacher' : 'student';
    $session = \App\Models\QuranSession::findOrFail($session);

    // Quran sessions use circle configuration
    $circle = $session->session_type === 'individual'
        ? $session->individualCircle
        : $session->circle;
    $preparationMinutes = $circle?->preparation_minutes ?? 15;
    $endingBufferMinutes = $circle?->ending_buffer_minutes ?? 5;

    // ... rest of the quran session logic (same logic as academic but with circle config)
    $now = now();
    $canJoinMeeting = false;
    $message = '';
    $buttonText = '';
    $buttonClass = '';

    // Check if user can join meeting based on timing and status
    if ($userType === 'quran_teacher' && in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING,
        App\Enums\SessionStatus::SCHEDULED,
    ])) {
        if ($session->scheduled_at) { // NULL check
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);
            if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
                $canJoinMeeting = true;
            }
        }
    } elseif ($userType === 'student' && in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING,
        App\Enums\SessionStatus::SCHEDULED,
    ])) {
        if ($session->scheduled_at) { // NULL check
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);
            if ($now->lt($sessionEnd)) {
                $canJoinMeeting = true;
            }
        }
    }

    // Determine message and button state based on session status
    $statusValue = is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : $session->status;

    switch ($session->status) {
        case App\Enums\SessionStatus::READY:
            $message = 'الجلسة جاهزة';
            $buttonText = 'غير متاح';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case App\Enums\SessionStatus::ONGOING:
            $message = 'الجلسة جارية حالياً';
            $buttonText = $canJoinMeeting ? 'انضم للجلسة' : 'غير متاح';
            $buttonClass = $canJoinMeeting ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-400 cursor-not-allowed';
            break;

        case App\Enums\SessionStatus::COMPLETED:
            $message = 'تم إنهاء الجلسة';
            $buttonText = 'الجلسة منتهية';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case App\Enums\SessionStatus::CANCELLED:
            $message = 'تم إلغاء الجلسة';
            $buttonText = 'الجلسة ملغية';
            $buttonClass = 'bg-red-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case App\Enums\SessionStatus::SCHEDULED:
            if ($canJoinMeeting) {
                $message = 'يمكنك الانضمام للجلسة الآن';
                $buttonText = 'انضم للجلسة';
                $buttonClass = 'bg-green-500 hover:bg-green-600';
            } else {
                if ($session->scheduled_at) { // NULL check
                    $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                    $timeData = formatTimeRemaining($preparationTime);
                    $message = ! $timeData['is_past'] ? "الجلسة ستكون متاحة خلال {$timeData['formatted']}" : 'الجلسة ستكون متاحة قريباً';
                } else {
                    $message = 'الجلسة محجوزة ولكن لم يتم تحديد موعد';
                }
                $buttonText = 'في انتظار الجلسة';
                $buttonClass = 'bg-blue-400 cursor-not-allowed';
                $canJoinMeeting = false;
            }
            break;

        case App\Enums\SessionStatus::UNSCHEDULED:
            $message = 'الجلسة غير مجدولة بعد';
            $buttonText = 'في انتظار الجدولة';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        default:
            $message = 'حالة غير معروفة';
            $buttonText = 'غير متاح';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
    }

    return response()->json([
        'status' => $statusValue,
        'message' => $message,
        'button_text' => $buttonText,
        'button_class' => $buttonClass,
        'can_join' => $canJoinMeeting,
        'session_type' => 'quran',
    ]);
})->name('api.quran-sessions.status');

// Session-type-specific attendance APIs
Route::get('/api/academic-sessions/{session}/attendance-status', function (Request $request, $session) {
    if (! auth()->check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $user = $request->user();
    $session = \App\Models\AcademicSession::findOrFail($session);

    // For completed sessions, use stored data to avoid sync errors
    $statusValue = is_object($session->status) && method_exists($session->status, 'value')
        ? $session->status->value
        : $session->status;

    if ($statusValue === 'completed') {
        // Get stored session report data
        $sessionReport = \App\Models\AcademicSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();

        if ($sessionReport) {
            $status = [
                'is_currently_in_meeting' => false,
                'attendance_status' => $sessionReport->attendance_status ?? 'absent',
                'attendance_percentage' => number_format($sessionReport->attendance_percentage ?? 0, 2),
                'duration_minutes' => $sessionReport->actual_attendance_minutes ?? 0,
                'join_count' => 0,
                'is_late' => $sessionReport->is_late ?? false,
                'late_minutes' => $sessionReport->late_minutes ?? 0,
                'last_updated' => $sessionReport->updated_at,
            ];
        } else {
            $status = [
                'is_currently_in_meeting' => false,
                'attendance_status' => 'absent',
                'attendance_percentage' => '0.00',
                'duration_minutes' => 0,
                'join_count' => 0,
            ];
        }
    } else {
        // For active sessions, use appropriate service
        $academicService = app(\App\Services\AcademicAttendanceService::class);
        $status = $academicService->getCurrentAttendanceStatus($session, $user);
    }

    return response()->json($status);
})->name('api.academic-sessions.attendance-status');

Route::get('/api/quran-sessions/{session}/attendance-status', function (Request $request, $session) {
    if (! auth()->check()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $user = $request->user();
    $session = \App\Models\QuranSession::findOrFail($session);

    // For completed sessions, use stored data to avoid sync errors
    $statusValue = is_object($session->status) && method_exists($session->status, 'value')
        ? $session->status->value
        : $session->status;

    if ($statusValue === 'completed') {
        // Get stored session report data
        $sessionReport = \App\Models\StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $user->id)
            ->first();

        if ($sessionReport) {
            $status = [
                'is_currently_in_meeting' => false,
                'attendance_status' => $sessionReport->attendance_status ?? 'absent',
                'attendance_percentage' => number_format($sessionReport->attendance_percentage ?? 0, 2),
                'duration_minutes' => $sessionReport->actual_attendance_minutes ?? 0,
                'join_count' => 0,
                'is_late' => $sessionReport->is_late ?? false,
                'late_minutes' => $sessionReport->late_minutes ?? 0,
                'last_updated' => $sessionReport->updated_at,
            ];
        } else {
            $status = [
                'is_currently_in_meeting' => false,
                'attendance_status' => 'absent',
                'attendance_percentage' => '0.00',
                'duration_minutes' => 0,
                'join_count' => 0,
            ];
        }
    } else {
        // For active sessions, use appropriate service
        $unifiedService = app(\App\Services\UnifiedAttendanceService::class);
        $status = $unifiedService->getCurrentAttendanceStatus($session, $user);
    }

    return response()->json($status);
})->name('api.quran-sessions.attendance-status');

// LEGACY: General session status API (for backward compatibility)
Route::get('/api/sessions/{session}/status', function (Request $request, $session) {
    // Check authentication first
    if (! auth()->check()) {
        return response()->json([
            'message' => 'يجب تسجيل الدخول لعرض حالة الجلسة',
            'status' => 'unauthenticated',
            'can_join' => false,
            'button_text' => 'يجب تسجيل الدخول',
            'button_class' => 'bg-gray-400 cursor-not-allowed',
        ], 401);
    }

    $user = $request->user();
    $userType = $user->hasRole('quran_teacher') ? 'quran_teacher' : 'student';

    // Smart session resolution - check both types and find the one that exists
    $academicSession = \App\Models\AcademicSession::find($session);
    $quranSession = \App\Models\QuranSession::find($session);

    // Use whichever exists (prioritize the one that actually has this ID)
    if ($academicSession && $quranSession) {
        // If both exist with same ID, determine by user context
        if ($user->hasRole('academic_teacher') || $user->studentProfile?->academicSessions()->where('id', $session)->exists()) {
            $session = $academicSession;
        } else {
            $session = $quranSession;
        }
    } elseif ($academicSession) {
        $session = $academicSession;
    } elseif ($quranSession) {
        $session = $quranSession;
    } else {
        abort(404, 'الجلسة غير موجودة');
    }

    // Get circle configuration
    if ($session instanceof \App\Models\AcademicSession) {
        // Academic sessions use default configuration
        $circle = null;
        $preparationMinutes = 15; // Default for academic sessions
        $endingBufferMinutes = 5;
    } else {
        // Quran sessions use circle configuration
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
        $preparationMinutes = $circle?->preparation_minutes ?? 15;
        $endingBufferMinutes = $circle?->ending_buffer_minutes ?? 5;
    }

    // Determine if user can join
    $canJoinMeeting = in_array($session->status, [
        SessionStatus::READY,
        SessionStatus::ONGOING,
    ]);

    // CRITICAL FIX: Teachers can ALWAYS join ongoing/ready sessions regardless of status
    if ($userType === 'quran_teacher' && in_array($session->status, [
        SessionStatus::READY,
        SessionStatus::ONGOING,
        SessionStatus::ABSENT,  // Teachers can join even if marked absent (student absence)
        SessionStatus::SCHEDULED,
    ])) {
        $now = now();
        // Only check timing if session is scheduled
        if ($session->scheduled_at) {
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);

            if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
                $canJoinMeeting = true;
            }
        }
    }

    // Allow students to join even if marked absent, as long as session is not completed
    if ($userType === 'student' && in_array($session->status, [
        SessionStatus::ABSENT,
        SessionStatus::SCHEDULED,
    ])) {
        $now = now();
        // Only check timing if session is scheduled
        if ($session->scheduled_at) {
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);

            if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
                $canJoinMeeting = true;
            }
        }
    }

    // Determine status messages and button styling
    $message = '';
    $buttonText = '';
    $buttonClass = '';

    switch ($session->status) {
        case SessionStatus::READY:
            if ($canJoinMeeting) {
                $message = $userType === 'quran_teacher'
                    ? 'الجلسة جاهزة للبدء - يمكنك الآن بدء الاجتماع'
                    : 'الجلسة جاهزة - انضم الآن';
                $buttonText = $userType === 'quran_teacher' ? 'بدء الجلسة' : 'انضم للجلسة';
                $buttonClass = 'bg-green-600 hover:bg-green-700';
                $buttonDisabled = false;
            } else {
                $message = $userType === 'quran_teacher'
                    ? 'الجلسة جاهزة للبدء - يمكنك الآن بدء الاجتماع'
                    : 'الجلسة جاهزة';
                $buttonText = $userType === 'quran_teacher' ? 'بدء الجلسة' : 'غير متاح';
                $buttonClass = $userType === 'quran_teacher' ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed';
                $buttonDisabled = $userType !== 'quran_teacher';
            }
            break;

        case SessionStatus::ONGOING:
            $message = 'الجلسة جارية الآن - انضم للمشاركة';
            $buttonText = 'انضمام للجلسة الجارية';
            $buttonClass = 'bg-orange-600 hover:bg-orange-700 animate-pulse';
            break;

        case SessionStatus::SCHEDULED:
            if ($canJoinMeeting) {
                $message = 'جاري تحضير الاجتماع - يمكنك الانضمام الآن';
                $buttonText = 'انضم للجلسة';
                $buttonClass = 'bg-blue-600 hover:bg-blue-700';
            } else {
                // Only calculate timing if session is scheduled
                if ($session->scheduled_at) {
                    $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                    $timeData = formatTimeRemaining($preparationTime);
                    $message = ! $timeData['is_past']
                        ? 'سيتم تحضير الاجتماع خلال '.$timeData['formatted']
                        : 'جاري تحضير الاجتماع...';
                } else {
                    $message = 'الجلسة محجوزة ولكن لم يتم تحديد موعد';
                }
                $buttonText = 'في انتظار تحضير الاجتماع';
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
            }
            break;

        case SessionStatus::ABSENT:
            if ($canJoinMeeting) {
                if ($userType === 'quran_teacher') {
                    $message = 'الجلسة نشطة - يمكنك بدء أو الانضمام للاجتماع';
                    $buttonText = 'انضم للجلسة';
                    $buttonClass = 'bg-green-600 hover:bg-green-700';
                } else {
                    $message = 'تم تسجيل غيابك ولكن يمكنك الانضمام الآن';
                    $buttonText = 'انضم للجلسة (غائب)';
                    $buttonClass = 'bg-yellow-600 hover:bg-yellow-700';
                }
            } else {
                if ($userType === 'quran_teacher') {
                    $message = 'انتهت فترة الجلسة';
                    $buttonText = 'الجلسة منتهية';
                    $buttonClass = 'bg-gray-400 cursor-not-allowed';
                } else {
                    $message = 'تم تسجيل غياب الطالب';
                    $buttonText = 'غياب الطالب';
                    $buttonClass = 'bg-red-400 cursor-not-allowed';
                }
            }
            break;

        case SessionStatus::COMPLETED:
            $message = 'تم إنهاء الجلسة بنجاح';
            $buttonText = 'الجلسة منتهية';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case SessionStatus::CANCELLED:
            $message = 'تم إلغاء الجلسة';
            $buttonText = 'الجلسة ملغية';
            $buttonClass = 'bg-red-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        case SessionStatus::UNSCHEDULED:
            $message = 'الجلسة غير مجدولة بعد';
            $buttonText = 'في انتظار الجدولة';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
            break;

        default:
            // Handle case where status might be a string or enum
            $statusLabel = is_object($session->status) && method_exists($session->status, 'label')
                ? $session->status->label()
                : (string) $session->status;
            $message = 'حالة الجلسة: '.$statusLabel;
            $buttonText = 'غير متاح';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $canJoinMeeting = false;
    }

    return response()->json([
        'status' => is_object($session->status) && method_exists($session->status, 'value')
            ? $session->status->value
            : $session->status,
        'can_join' => $canJoinMeeting,
        'message' => $message,
        'button_text' => $buttonText,
        'button_class' => $buttonClass,
        'session_info' => [
            'scheduled_at' => $session->scheduled_at?->toISOString(),
            'duration_minutes' => $session->duration_minutes,
            'preparation_minutes' => $preparationMinutes,
            'meeting_room_name' => $session->meeting_room_name,
        ],
    ]);
})->name('api.sessions.status');

Route::get('/api/sessions/{session}/attendance-status', function (Request $request, $session) {
    $user = $request->user();

    // Smart session resolution - check both types and find the one that exists
    $academicSession = \App\Models\AcademicSession::find($session);
    $quranSession = \App\Models\QuranSession::find($session);

    // Use whichever exists (prioritize the one that actually has this ID)
    if ($academicSession && $quranSession) {
        // If both exist with same ID, determine by user context
        if ($user->hasRole('academic_teacher') || $user->studentProfile?->academicSessions()->where('id', $session)->exists()) {
            $session = $academicSession;
        } else {
            $session = $quranSession;
        }
    } elseif ($academicSession) {
        $session = $academicSession;
    } elseif ($quranSession) {
        $session = $quranSession;
    } else {
        abort(404, 'الجلسة غير موجودة');
    }

    $statusValue = is_object($session->status) && method_exists($session->status, 'value')
        ? $session->status->value
        : $session->status;

    // Check if user has ever joined this session
    $meetingAttendance = \App\Models\MeetingAttendance::where('session_id', $session->id)
        ->where('user_id', $user->id)
        ->first();

    $hasEverJoined = $meetingAttendance !== null;

    // ENHANCED: Handle session timing and states properly
    $now = now();
    $sessionStart = $session->scheduled_at;
    $sessionEnd = $sessionStart ? $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60) : null;

    // Determine session timing state
    $isBeforeSession = $sessionStart && $now->isBefore($sessionStart);
    $isDuringSession = $sessionStart && $sessionEnd && $now->between($sessionStart, $sessionEnd);
    $isAfterSession = $sessionEnd && $now->isAfter($sessionEnd);

    // For completed sessions OR sessions that have ended by time
    if ($statusValue === 'completed' || $isAfterSession) {
        // Get stored session report data based on session type
        if ($session instanceof \App\Models\AcademicSession) {
            $sessionReport = \App\Models\AcademicSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();
        } else {
            $sessionReport = \App\Models\StudentSessionReport::where('session_id', $session->id)
                ->where('student_id', $user->id)
                ->first();
        }

        if ($sessionReport) {
            // Use report data but enhance attendance status description
            $attendanceStatus = $sessionReport->attendance_status ?? 'absent';
            $duration = $sessionReport->actual_attendance_minutes ?? 0;

            // Enhanced status for completed sessions
            if (! $hasEverJoined) {
                $attendanceStatus = 'not_attended'; // Never joined
            } elseif ($duration > 0 && $attendanceStatus === 'partial') {
                $attendanceStatus = 'partial_attendance'; // Joined but insufficient time
            }

            $status = [
                'is_currently_in_meeting' => false, // Session is over
                'attendance_status' => $attendanceStatus,
                'attendance_percentage' => number_format($sessionReport->attendance_percentage ?? 0, 2),
                'duration_minutes' => $duration,
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'is_late' => $sessionReport->is_late ?? false,
                'late_minutes' => $sessionReport->late_minutes ?? 0,
                'last_updated' => $sessionReport->updated_at,
                'session_state' => 'completed',
                'has_ever_joined' => $hasEverJoined,
            ];
        } else {
            // No report exists - student never participated
            $status = [
                'is_currently_in_meeting' => false,
                'attendance_status' => $hasEverJoined ? 'not_enough_time' : 'not_attended',
                'attendance_percentage' => '0.00',
                'duration_minutes' => $meetingAttendance?->total_duration_minutes ?? 0,
                'join_count' => $meetingAttendance?->join_count ?? 0,
                'session_state' => 'completed',
                'has_ever_joined' => $hasEverJoined,
            ];
        }
    } elseif ($isBeforeSession) {
        // Session hasn't started yet
        $status = [
            'is_currently_in_meeting' => false,
            'attendance_status' => 'not_started',
            'attendance_percentage' => '0.00',
            'duration_minutes' => 0,
            'join_count' => 0,
            'session_state' => 'scheduled',
            'has_ever_joined' => false,
            'minutes_until_start' => max(0, ceil($now->diffInMinutes($sessionStart, false))),
        ];
    } else {
        // Session is active or scheduled - use real-time data
        if ($session instanceof \App\Models\AcademicSession) {
            $academicService = app(\App\Services\AcademicAttendanceService::class);
            $status = $academicService->getCurrentAttendanceStatus($session, $user);
        } else {
            $unifiedService = app(\App\Services\UnifiedAttendanceService::class);
            $status = $unifiedService->getCurrentAttendanceStatus($session, $user);
        }

        // Add session state info
        $status['session_state'] = $isDuringSession ? 'ongoing' : 'scheduled';
        $status['has_ever_joined'] = $hasEverJoined;

        // Override status for users who haven't joined yet during scheduled time
        if (! $hasEverJoined && ($statusValue === 'scheduled' || $isDuringSession)) {
            $status['attendance_status'] = 'not_joined_yet';
        }
    }

    return response()->json($status);
})->name('api.sessions.attendance-status');

// TEMPORARY: Test API endpoints accessibility
Route::get('/debug-api-test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API endpoints are working!',
        'time' => now(),
        'routes_exist' => [
            'status' => \Illuminate\Support\Facades\Route::has('api.sessions.status'),
            'attendance' => \Illuminate\Support\Facades\Route::has('api.sessions.attendance-status'),
        ],
    ]);
});

// Test route WITHOUT authentication from subdomain
Route::get('/api/test-no-auth', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'No auth test working!',
        'subdomain' => $request->route('subdomain') ?? 'none',
        'host' => $request->getHost(),
        'url' => $request->url(),
        'headers' => [
            'origin' => $request->header('Origin'),
            'referer' => $request->header('Referer'),
            'user-agent' => $request->header('User-Agent'),
        ],
    ]);
});

// Test route WITH authentication from subdomain
Route::middleware(['auth'])->get('/api/test-with-auth', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Auth test working!',
        'user_id' => auth()->id(),
        'user_type' => auth()->user()->user_type ?? null,
        'authenticated' => auth()->check(),
        'subdomain' => $request->route('subdomain') ?? 'none',
        'host' => $request->getHost(),
        'session_id' => session()->getId(),
    ]);
});

// DEBUG: Catch-all route to see what requests are coming in
Route::get('/api/debug-requests/{path?}', function (Request $request, $path = null) {
    return response()->json([
        'message' => 'Debug: Request received',
        'path' => $path,
        'full_url' => $request->fullUrl(),
        'method' => $request->method(),
        'headers' => $request->headers->all(),
        'route_params' => $request->route()->parameters ?? [],
        'query_params' => $request->query(),
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'timestamp' => now(),
    ]);
})->where('path', '.*');

// Test routes for academy styling verification
Route::get('/test-academy', function () {
    $academy = \App\Models\Academy::where('subdomain', 'itqan-academy')->first();
    if (! $academy) {
        return 'Academy not found';
    }

    $stats = [
        'total_students' => 150,
        'total_teachers' => 25,
        'active_courses' => 45,
        'quran_circles' => 12,
        'completion_rate' => 85,
    ];

    $services = [
        'quran_circles' => collect(),
        'quran_teacher_profiles' => collect(),
        'interactive_courses' => collect(),
        'academic_teachers' => collect(),
        'recorded_courses' => collect(),
    ];

    return view('academy.homepage', compact('academy', 'stats', 'services'));
});

// Dynamic test routes for each academy (localhost development)
Route::get('/academy/{subdomain}', function ($subdomain) {
    $academy = \App\Models\Academy::where('subdomain', $subdomain)->first();
    if (! $academy) {
        return redirect('/')->with('error', 'Academy not found: '.$subdomain);
    }

    // Make academy available for the view
    app()->instance('current_academy', $academy);

    $stats = [
        'total_students' => rand(50, 200),
        'total_teachers' => rand(10, 50),
        'active_courses' => rand(20, 80),
        'quran_circles' => rand(5, 20),
        'completion_rate' => rand(75, 95),
    ];

    $services = [
        'quran_circles' => collect(),
        'quran_teacher_profiles' => collect(),
        'interactive_courses' => collect(),
        'academic_teachers' => collect(),
        'recorded_courses' => collect(),
    ];

    return view('academy.homepage', compact('academy', 'stats', 'services'));
});

/*
|--------------------------------------------------------------------------
| Main Domain Routes (Platform Landing Page)
|--------------------------------------------------------------------------
*/

// Main domain routes (itqan-platform.test) - Platform landing page
Route::domain(config('app.domain'))->group(function () {

    // Platform Landing Page
    Route::get('/', [App\Http\Controllers\PlatformController::class, 'home'])->name('platform.home');

    // Platform About Page
    Route::get('/about', function () {
        return view('platform.about');
    })->name('platform.about');

    // Platform Contact Page
    Route::get('/contact', function () {
        return view('platform.contact');
    })->name('platform.contact');

    // Platform Features Page
    Route::get('/features', function () {
        return view('platform.features');
    })->name('platform.features');

    // Business Services
    Route::get('/business-services', [\App\Http\Controllers\BusinessServiceController::class, 'index'])
        ->name('platform.business-services');

    // Portfolio
    Route::get('/portfolio', [\App\Http\Controllers\BusinessServiceController::class, 'portfolio'])
        ->name('platform.portfolio');

    // Business Service Request API
    Route::post('/business-services/request', [\App\Http\Controllers\BusinessServiceController::class, 'storeRequest'])
        ->name('platform.business-services.request');

    // Business Service Categories API
    Route::get('/business-services/categories', [\App\Http\Controllers\BusinessServiceController::class, 'getCategories'])
        ->name('platform.business-services.categories');

    // Portfolio Items API
    Route::get('/business-services/portfolio', [\App\Http\Controllers\BusinessServiceController::class, 'getPortfolioItems'])
        ->name('platform.business-services.portfolio-items');

    // Admin Panel (Super Admin)
    Route::get('/admin', function () {
        return redirect('/admin/login');
    });

    // Keep the old-home route for reference (can be removed later)
    Route::get('/old-home', function () {
        // Check if there's a default academy (itqan-academy)
        $defaultAcademy = Academy::where('subdomain', 'itqan-academy')->first();

        if ($defaultAcademy) {
            $output = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 8px;'>
                <h1 style='color: #2563eb;'>🎓 Itqan Platform</h1>
                <p><strong>Default Academy:</strong> {$defaultAcademy->name}</p>
                <p><strong>Domain:</strong> ".request()->getHost().'</p>
                <hr>
                <h3>Available Academies:</h3>';

            $academies = Academy::where('is_active', true)->where('maintenance_mode', false)->get();
            foreach ($academies as $academy) {
                $output .= "<p><a href='http://{$academy->full_domain}' style='color: #2563eb; text-decoration: none;'>{$academy->name} ({$academy->subdomain})</a></p>";
            }

            $output .= "
                <hr>
                <a href='/admin' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px;'>Admin Panel</a>
            </div>
            ";

            return $output;
        }

        return view('welcome');
    });

    // Catch-all for other routes - redirect to appropriate academy
    Route::fallback(function () {
        $path = request()->path();

        // Don't redirect admin routes
        if (str_starts_with($path, 'admin')) {
            abort(404);
        }

        // Redirect learning-related routes to default academy
        if (in_array($path, ['login', 'register', 'dashboard', 'profile', 'courses', 'quran-teachers', 'quran-circles', 'student/register', 'teacher/register'])) {
            return redirect('http://itqan-academy.'.config('app.domain').'/'.$path);
        }

        // For other routes, show 404 or redirect to platform home
        abort(404);
    });
});

/*
|--------------------------------------------------------------------------
| Subdomain Routes
|--------------------------------------------------------------------------
*/

// Subdomain routes ({subdomain}.itqan-platform.test)
Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    // Academy Home Page
    Route::get('/', [AcademyHomepageController::class, 'show'])->name('academy.home');

    /*
    |--------------------------------------------------------------------------
    | Course Management Routes
    |--------------------------------------------------------------------------
    */

    // Course Listing & Discovery (Public Access)
    Route::get('/courses', [RecordedCourseController::class, 'index'])->name('courses.index');

    // Course Management (Admin/Teacher Only)
    Route::middleware(['auth', 'role:admin,teacher,quran_teacher,academic_teacher'])->group(function () {
        Route::get('/courses/create', [RecordedCourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [RecordedCourseController::class, 'store'])->name('courses.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Lesson & Learning Routes
    |--------------------------------------------------------------------------
    | MUST COME BEFORE course routes to avoid conflicts
    */

    // Lesson Viewing & Progress (ID-based) - Specific routes first to avoid conflicts
    Route::get('/courses/{courseId}/lessons/{lessonId}/progress', [LessonController::class, 'getProgress'])->name('lessons.progress.get')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/progress', [LessonController::class, 'updateProgress'])->name('lessons.progress.update')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [LessonController::class, 'markComplete'])->name('lessons.complete')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}', [LessonController::class, 'show'])->name('lessons.show')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    // Lesson Interactions
    Route::post('/courses/{courseId}/lessons/{lessonId}/bookmark', [LessonController::class, 'addBookmark'])->name('lessons.bookmark')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::delete('/courses/{courseId}/lessons/{lessonId}/bookmark', [LessonController::class, 'removeBookmark'])->name('lessons.unbookmark')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/notes', [LessonController::class, 'addNote'])->name('lessons.notes.add')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/notes', [LessonController::class, 'getNotes'])->name('lessons.notes.get')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/rate', [LessonController::class, 'rate'])->name('lessons.rate')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    // Lesson Resources
    Route::get('/courses/{courseId}/lessons/{lessonId}/transcript', [LessonController::class, 'getTranscript'])->name('lessons.transcript')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/materials', [LessonController::class, 'downloadMaterials'])->name('lessons.materials')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/video', [LessonController::class, 'serveVideo'])->name('lessons.video')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::options('/courses/{courseId}/lessons/{lessonId}/video', [LessonController::class, 'serveVideoOptions'])->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    // Course progress route
    Route::get('/courses/{courseId}/progress', [RecordedCourseController::class, 'getProgress'])
        ->name('courses.progress')
        ->where('courseId', '[0-9]+');

    // API Progress routes (using web middleware for session auth)
    Route::middleware('auth')->prefix('api')->group(function () {
        Route::get('/courses/{courseId}/progress', [App\Http\Controllers\Api\ProgressController::class, 'getCourseProgress']);
        Route::get('/courses/{courseId}/lessons/{lessonId}/progress', [App\Http\Controllers\Api\ProgressController::class, 'getLessonProgress']);
        Route::post('/courses/{courseId}/lessons/{lessonId}/progress', [App\Http\Controllers\Api\ProgressController::class, 'updateLessonProgress']);
        Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [App\Http\Controllers\Api\ProgressController::class, 'markLessonComplete']);
    });

    // Course Detail - Public Access (shows different content based on auth status)
    Route::get('/courses/{id}', [RecordedCourseController::class, 'show'])
        ->name('courses.show')
        ->where('id', '[0-9]+');

    // Course Enrollment (Requires Authentication)
    Route::middleware(['auth'])->group(function () {
        Route::post('/courses/{id}/enroll', [RecordedCourseController::class, 'enroll'])->name('courses.enroll')->where('id', '[0-9]+');
        Route::post('/api/courses/{id}/enroll', [RecordedCourseController::class, 'enrollApi'])->name('courses.enroll.api')->where('id', '[0-9]+');
        Route::get('/courses/{id}/checkout', [RecordedCourseController::class, 'checkout'])->name('courses.checkout')->where('id', '[0-9]+');
        Route::get('/courses/{id}/learn', [RecordedCourseController::class, 'learn'])->name('courses.learn')->where('id', '[0-9]+');
    });

    // Legacy redirect for backward compatibility
    Route::get('/course/{id}', function ($subdomain, $id) {
        return redirect()->route('courses.show', ['subdomain' => $subdomain, 'id' => $id]);
    })->where('id', '[0-9]+');

    /*
    |--------------------------------------------------------------------------
    | Payment Routes
    |--------------------------------------------------------------------------
    */

    // Payment Processing (ID-based)
    Route::get('/courses/{courseId}/payment', [PaymentController::class, 'create'])->name('payments.create')->where('courseId', '[0-9]+');
    Route::post('/courses/{courseId}/payment', [PaymentController::class, 'store'])->name('payments.store')->where('courseId', '[0-9]+');
    Route::get('/payments/{payment}/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/{payment}/failed', [PaymentController::class, 'failed'])->name('payments.failed');

    // Payment Management
    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    // Payment Methods API
    Route::get('/api/payment-methods/{academy}', [PaymentController::class, 'getPaymentMethods'])->name('api.payment-methods');

    /*
    |--------------------------------------------------------------------------
    | Student Dashboard Routes
    |--------------------------------------------------------------------------
    */

    // Main Dashboard
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    // Note: student.courses functionality is now handled by courses.index route (unified for both public and students)
    // Route::get('/my-courses', function () {
    //     return redirect()->route('courses.index', ['subdomain' => request()->route('subdomain')]);
    // })->name('student.courses');
    Route::get('/enrollments/{enrollment}/progress', [StudentDashboardController::class, 'courseProgress'])->name('student.course-progress');

    // Learning Resources
    Route::get('/certificates', [StudentDashboardController::class, 'certificates'])->name('student.certificates');
    Route::get('/enrollments/{enrollment}/certificate', [StudentDashboardController::class, 'downloadCertificate'])->name('student.certificate.download');
    Route::get('/bookmarks', [StudentDashboardController::class, 'bookmarks'])->name('student.bookmarks');
    Route::get('/notes', [StudentDashboardController::class, 'notes'])->name('student.notes');

    // Learning Analytics
    Route::get('/analytics', [StudentDashboardController::class, 'analytics'])->name('student.analytics');

    /*
    |--------------------------------------------------------------------------
    | Student Profile Routes
    |--------------------------------------------------------------------------
    | Note: Some routes defined here due to route registration issues in auth.php
    */

    // Missing student routes that weren't registering from auth.php
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/profile', [App\Http\Controllers\StudentProfileController::class, 'index'])->name('student.profile');
        Route::get('/my-quran-teachers', [App\Http\Controllers\StudentProfileController::class, 'quranTeachers'])->name('student.quran-teachers');
        Route::get('/payments', [App\Http\Controllers\StudentProfileController::class, 'payments'])->name('student.payments');
        Route::get('/my-quran-circles', [App\Http\Controllers\StudentProfileController::class, 'quranCircles'])->name('student.quran-circles');
        Route::get('/my-academic-teachers', [App\Http\Controllers\StudentProfileController::class, 'academicTeachers'])->name('student.academic-teachers');

        // Student session routes (moved from auth.php for subdomain compatibility)
        Route::get('/sessions/{sessionId}', [App\Http\Controllers\QuranSessionController::class, 'showForStudent'])->name('student.sessions.show');
        Route::put('/sessions/{sessionId}/feedback', [App\Http\Controllers\QuranSessionController::class, 'addFeedback'])->name('student.sessions.feedback');

        // Academic subscription routes for students
        Route::get('/academic-subscriptions/{subscriptionId}', [App\Http\Controllers\StudentProfileController::class, 'showAcademicSubscription'])->name('student.academic-subscriptions.show');

        // Academic session routes for students
        Route::get('/academic-sessions/{sessionId}', [App\Http\Controllers\StudentProfileController::class, 'showAcademicSession'])->name('student.academic-sessions.show');
        Route::put('/academic-sessions/{sessionId}/feedback', [App\Http\Controllers\AcademicSessionController::class, 'addStudentFeedback'])->name('student.academic-sessions.feedback');
        Route::post('/academic-sessions/{sessionId}/homework', [App\Http\Controllers\AcademicSessionController::class, 'submitHomework'])->name('student.academic-sessions.homework.submit');

        // Homework routes for students
        Route::prefix('homework')->name('student.homework.')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\HomeworkController::class, 'index'])->name('index');
            Route::get('/{id}/submit', [App\Http\Controllers\Student\HomeworkController::class, 'submit'])->name('submit');
            Route::post('/{id}/submit', [App\Http\Controllers\Student\HomeworkController::class, 'submitProcess'])->name('submit.process');
            Route::get('/{id}/view', [App\Http\Controllers\Student\HomeworkController::class, 'view'])->name('view');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Additional Academy Routes
    |--------------------------------------------------------------------------
    */

    // These can be extended for other features
    // Route::get('/teachers', [TeachersController::class, 'index'])->name('teachers.index');
    // Route::get('/subjects', [SubjectsController::class, 'index'])->name('subjects.index');
    // Route::get('/about', [AcademyController::class, 'about'])->name('academy.about');
    // Route::get('/contact', [AcademyController::class, 'contact'])->name('academy.contact');

    /*
    |--------------------------------------------------------------------------
    | Public Quran Teacher Profile Routes
    |--------------------------------------------------------------------------
    */

    // Public Quran Teachers Listing
    Route::get('/quran-teachers', [App\Http\Controllers\PublicQuranTeacherController::class, 'index'])->name('public.quran-teachers.index');

    // Individual Teacher Profile Pages
    Route::get('/quran-teachers/{teacher}', [App\Http\Controllers\PublicQuranTeacherController::class, 'show'])->name('public.quran-teachers.show');

    /*
    |--------------------------------------------------------------------------
    | Public Academic Teacher Profile Routes
    |--------------------------------------------------------------------------
    */

    // Public Academic Teachers Listing
    Route::get('/academic-teachers', [App\Http\Controllers\PublicAcademicTeacherController::class, 'index'])->name('public.academic-teachers.index');

    // Individual Academic Teacher Profile Pages
    Route::get('/academic-teachers/{teacher}', [App\Http\Controllers\PublicAcademicTeacherController::class, 'show'])->name('public.academic-teachers.show');

    /*
    |--------------------------------------------------------------------------
    | Public Academic Package Routes
    |--------------------------------------------------------------------------
    */

    // Public Academic Packages Listing
    Route::get('/academic-packages', [App\Http\Controllers\PublicAcademicPackageController::class, 'index'])->name('public.academic-packages.index');

    // Individual Teacher Profile for Academic Packages
    Route::get('/academic-packages/teachers/{teacher}', [App\Http\Controllers\PublicAcademicPackageController::class, 'showTeacher'])->name('public.academic-packages.teacher');

    // API: Get teachers for a specific package
    Route::get('/api/academic-packages/{packageId}/teachers', [App\Http\Controllers\PublicAcademicPackageController::class, 'getPackageTeachers'])->name('api.academic-packages.teachers');

    // Trial Session Booking (requires auth)
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/quran-teachers/{teacher}/trial', [App\Http\Controllers\PublicQuranTeacherController::class, 'showTrialBooking'])->name('public.quran-teachers.trial');
        Route::post('/quran-teachers/{teacher}/trial', [App\Http\Controllers\PublicQuranTeacherController::class, 'submitTrialRequest'])->name('public.quran-teachers.trial.submit');

        Route::get('/quran-teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicQuranTeacherController::class, 'showSubscriptionBooking'])->name('public.quran-teachers.subscribe');
        Route::post('/quran-teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicQuranTeacherController::class, 'submitSubscriptionRequest'])->name('public.quran-teachers.subscribe.submit');

        // Academic Package Subscription
        Route::get('/academic-packages/teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicAcademicPackageController::class, 'showSubscriptionForm'])->name('public.academic-packages.subscribe');
        Route::post('/academic-packages/teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicAcademicPackageController::class, 'submitSubscriptionRequest'])->name('public.academic-packages.subscribe.submit');

        // Student Academic Sessions
        Route::get('/academic-sessions/{sessionId}', [App\Http\Controllers\StudentProfileController::class, 'showAcademicSession'])->name('student.academic-sessions.show');

        // Quran Subscription Payment
        Route::get('/quran/subscription/{subscription}/payment', [App\Http\Controllers\QuranSubscriptionPaymentController::class, 'create'])->name('quran.subscription.payment');
        Route::post('/quran/subscription/{subscription}/payment', [App\Http\Controllers\QuranSubscriptionPaymentController::class, 'store'])->name('quran.subscription.payment.submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Public Quran Circle Routes
    |--------------------------------------------------------------------------
    */

    // Public Quran Circles Listing
    Route::get('/quran-circles', [App\Http\Controllers\PublicQuranCircleController::class, 'index'])->name('public.quran-circles.index');

    // Individual Circle Details Pages
    Route::get('/quran-circles/{circle}', [App\Http\Controllers\PublicQuranCircleController::class, 'show'])->name('public.quran-circles.show');

    // Circle Enrollment (requires auth)
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/quran-circles/{circle}/enroll', [App\Http\Controllers\PublicQuranCircleController::class, 'showEnrollment'])->name('public.quran-circles.enroll');
        Route::post('/quran-circles/{circle}/enroll', [App\Http\Controllers\PublicQuranCircleController::class, 'submitEnrollment'])->name('public.quran-circles.enroll.submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Public Interactive Courses Routes
    |--------------------------------------------------------------------------
    */

    // Public Interactive Courses Listing
    Route::get('/public/interactive-courses', [App\Http\Controllers\PublicInteractiveCourseController::class, 'index'])->name('public.interactive-courses.index');
    Route::get('/interactive-courses', [App\Http\Controllers\PublicInteractiveCourseController::class, 'index'])->name('interactive-courses.index');

    // Individual Interactive Course Details
    Route::get('/public/interactive-courses/{course}', [App\Http\Controllers\PublicInteractiveCourseController::class, 'show'])->name('public.interactive-courses.show');
    Route::get('/interactive-courses/{course}', [App\Http\Controllers\PublicInteractiveCourseController::class, 'show'])->name('interactive-courses.show');

    // Interactive Course Enrollment
    Route::get('/interactive-courses/{course}/enroll', [App\Http\Controllers\PublicInteractiveCourseController::class, 'enroll'])->name('interactive-courses.enroll');
    Route::post('/interactive-courses/{course}/enroll', [App\Http\Controllers\PublicInteractiveCourseController::class, 'storeEnrollment'])->name('interactive-courses.store-enrollment');

    /*
    |--------------------------------------------------------------------------
    | Public Recorded Courses Routes
    |--------------------------------------------------------------------------
    */

    // Public Recorded Courses Listing
    Route::get('/public/recorded-courses', [App\Http\Controllers\PublicRecordedCourseController::class, 'index'])->name('public.recorded-courses.index');

    // Individual Recorded Course Details
    Route::get('/public/recorded-courses/{course}', [App\Http\Controllers\PublicRecordedCourseController::class, 'show'])->name('public.recorded-courses.show');

    /*
    |--------------------------------------------------------------------------
    /*
    |--------------------------------------------------------------------------
    | Teacher Calendar Routes
    |--------------------------------------------------------------------------
    */

    // Teacher calendar routes
    Route::middleware(['auth', 'role:quran_teacher,academic_teacher'])->group(function () {
        Route::get('/teacher/calendar', [App\Http\Controllers\TeacherCalendarController::class, 'index'])->name('teacher.calendar');
        Route::get('/teacher/calendar/events', [App\Http\Controllers\TeacherCalendarController::class, 'getEvents'])->name('teacher.calendar.events');
        Route::post('/teacher/calendar/sessions', [App\Http\Controllers\TeacherCalendarController::class, 'createSession'])->name('teacher.calendar.create-session');
        Route::put('/teacher/calendar/sessions/{session}', [App\Http\Controllers\TeacherCalendarController::class, 'updateSession'])->name('teacher.calendar.update-session');
        Route::delete('/teacher/calendar/sessions/{session}', [App\Http\Controllers\TeacherCalendarController::class, 'deleteSession'])->name('teacher.calendar.delete-session');
        Route::post('/teacher/calendar/bulk-update', [App\Http\Controllers\TeacherCalendarController::class, 'bulkUpdate'])->name('teacher.calendar.bulk-update');

        // New Calendar API routes
        Route::get('/teacher/api/circles', [App\Http\Controllers\Teacher\CalendarApiController::class, 'getCircles'])->name('teacher.api.circles');
        Route::post('/teacher/api/bulk-schedule', [App\Http\Controllers\Teacher\CalendarApiController::class, 'bulkSchedule'])->name('teacher.api.bulk-schedule');

        // Homework grading routes for teachers
        Route::prefix('teacher/homework')->name('teacher.homework.')->group(function () {
            Route::get('/', [App\Http\Controllers\Teacher\HomeworkGradingController::class, 'index'])->name('index');
            Route::get('/{submissionId}/grade', [App\Http\Controllers\Teacher\HomeworkGradingController::class, 'grade'])->name('grade');
            Route::post('/{submissionId}/grade', [App\Http\Controllers\Teacher\HomeworkGradingController::class, 'gradeProcess'])->name('grade.process');
            Route::post('/{submissionId}/revision', [App\Http\Controllers\Teacher\HomeworkGradingController::class, 'requestRevision'])->name('request-revision');
            Route::get('/statistics', [App\Http\Controllers\Teacher\HomeworkGradingController::class, 'statistics'])->name('statistics');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Unified Individual Circles Routes
    |--------------------------------------------------------------------------
    */

    // Unified routes accessible by authenticated users (authorization enforced in controller)
    Route::middleware(['auth'])->group(function () {
        Route::get('/individual-circles/{circle}', [App\Http\Controllers\QuranIndividualCircleController::class, 'show'])
            ->name('individual-circles.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher Individual Circles Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:quran_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        // Quran-specific routes only (profile routes moved to auth.php for all teachers)

        // Individual Circles Management
        Route::get('/individual-circles', [App\Http\Controllers\QuranIndividualCircleController::class, 'index'])->name('individual-circles.index');
        Route::get('/individual-circles/{circle}/progress', [App\Http\Controllers\QuranIndividualCircleController::class, 'progressReport'])->name('individual-circles.progress');

        // AJAX routes for individual circles
        Route::get('/individual-circles/{circle}/template-sessions', [App\Http\Controllers\QuranIndividualCircleController::class, 'getTemplateSessions'])->name('individual-circles.template-sessions');
        Route::put('/individual-circles/{circle}/settings', [App\Http\Controllers\QuranIndividualCircleController::class, 'updateSettings'])->name('individual-circles.update-settings');

        // Student Reports API Routes
        Route::prefix('student-reports')->name('student-reports.')->group(function () {
            Route::get('{reportId}', [App\Http\Controllers\Teacher\StudentReportController::class, 'show'])->name('show');
            Route::post('update', [App\Http\Controllers\Teacher\StudentReportController::class, 'updateEvaluation'])->name('update');
            Route::post('sessions/{sessionId}/generate', [App\Http\Controllers\Teacher\StudentReportController::class, 'generateSessionReports'])->name('generate-session');
            Route::get('sessions/{sessionId}/stats', [App\Http\Controllers\Teacher\StudentReportController::class, 'getSessionStats'])->name('session-stats');
        });

        // Student basic info API
        Route::get('students/{studentId}/basic-info', [App\Http\Controllers\Teacher\StudentReportController::class, 'getStudentBasicInfo'])->name('students.basic-info');

        // Session Homework Management Routes
        Route::prefix('sessions/{sessionId}/homework')->name('sessions.homework.')->group(function () {
            Route::get('', [App\Http\Controllers\Teacher\SessionHomeworkController::class, 'show'])->name('show');
            Route::post('', [App\Http\Controllers\Teacher\SessionHomeworkController::class, 'createOrUpdate'])->name('create-or-update');
            Route::delete('', [App\Http\Controllers\Teacher\SessionHomeworkController::class, 'destroy'])->name('destroy');
        });

    });

    /*
    |--------------------------------------------------------------------------
    | Teacher Group Circles Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:quran_teacher,admin,super_admin'])->prefix('teacher')->name('teacher.')->group(function () {
        // Group Circles Management
        Route::get('/group-circles', [App\Http\Controllers\QuranGroupCircleScheduleController::class, 'index'])->name('group-circles.index');
        Route::get('/group-circles/{circle}', [App\Http\Controllers\QuranGroupCircleScheduleController::class, 'show'])->name('group-circles.show');
        Route::get('/group-circles/{circle}/progress', [App\Http\Controllers\QuranGroupCircleScheduleController::class, 'progressReport'])->name('group-circles.progress');
        Route::get('/group-circles/{circle}/students/{student}/progress', [App\Http\Controllers\QuranGroupCircleScheduleController::class, 'studentProgressReport'])->name('group-circles.student-progress');

        // Session management routes
        Route::get('/sessions/{sessionId}', [App\Http\Controllers\QuranSessionController::class, 'showForTeacher'])->name('sessions.show');
        Route::put('/sessions/{sessionId}/notes', [App\Http\Controllers\QuranSessionController::class, 'updateNotes'])->name('sessions.update-notes');
        Route::put('/sessions/{sessionId}/complete', [App\Http\Controllers\QuranSessionController::class, 'markCompleted'])->name('sessions.complete');
        Route::put('/sessions/{sessionId}/cancel', [App\Http\Controllers\QuranSessionController::class, 'markCancelled'])->name('sessions.cancel');
        Route::put('/sessions/{sessionId}/absent', [App\Http\Controllers\QuranSessionController::class, 'markAbsent'])->name('sessions.absent');
        Route::get('/sessions/{sessionId}/actions', [App\Http\Controllers\QuranSessionController::class, 'getStatusActions'])->name('sessions.actions');
        Route::post('/sessions/{sessionId}/create-meeting', [App\Http\Controllers\LiveKitMeetingController::class, 'createMeeting'])->name('sessions.create-meeting');
    });

    /*
    |--------------------------------------------------------------------------
    | Student Calendar Routes
    |--------------------------------------------------------------------------
    */

    // Student calendar routes
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/student/calendar', [App\Http\Controllers\StudentCalendarController::class, 'index'])->name('student.calendar');
        Route::get('/student/calendar/events', [App\Http\Controllers\StudentCalendarController::class, 'getEvents'])->name('student.calendar.events');
    });

    /*
    |--------------------------------------------------------------------------
    | Google OAuth Routes
    |--------------------------------------------------------------------------
    */

    // Google OAuth for teachers
    Route::middleware(['auth', 'role:quran_teacher,academic_teacher'])->group(function () {
        Route::get('/google/auth', [App\Http\Controllers\GoogleAuthController::class, 'redirect'])->name('google.auth');
        Route::get('/google/callback', [App\Http\Controllers\GoogleAuthController::class, 'callback'])->name('google.callback');
        Route::post('/google/disconnect', [App\Http\Controllers\GoogleAuthController::class, 'disconnect'])->name('google.disconnect');
    });

    /*
    |--------------------------------------------------------------------------
    | Session API Routes (for subdomain AJAX requests)
    |--------------------------------------------------------------------------
    */

    // CSRF token endpoint for AJAX requests
    Route::get('/csrf-token', function () {
        return response()->json([
            'token' => csrf_token(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Student Interactive Courses Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/interactive-courses', [App\Http\Controllers\StudentProfileController::class, 'interactiveCourses'])->name('student.interactive-courses');
    });

    // Interactive course detail - accessible by enrolled students and teachers
    Route::middleware(['auth', 'interactive.course'])->group(function () {
        Route::get('/my-courses/interactive/{course}', [App\Http\Controllers\StudentProfileController::class, 'showInteractiveCourse'])->name('my.interactive-course.show');
    });

    // Interactive course session detail - for enrolled students
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/interactive-sessions/{session}', [App\Http\Controllers\StudentProfileController::class, 'showInteractiveCourseSession'])->name('student.interactive-sessions.show');
        Route::post('/interactive-sessions/{session}/feedback', [App\Http\Controllers\StudentProfileController::class, 'addInteractiveSessionFeedback'])->name('student.interactive-sessions.feedback');
        Route::post('/interactive-sessions/{session}/homework', [App\Http\Controllers\StudentProfileController::class, 'submitInteractiveCourseHomework'])->name('student.interactive-sessions.homework');
    });

    // Chat Route - Role-based views (within subdomain group)
    Route::middleware(['auth'])->get('/chat', function (Request $request) {
        $user = auth()->user();
        $userType = $user->user_type;

        // Map user types to view names
        $viewMap = [
            'student' => 'chat.student',
            'quran_teacher' => 'chat.teacher',
            'academic_teacher' => 'chat.academic-teacher',
            'parent' => 'chat.parent',
            'supervisor' => 'chat.supervisor',
            'academy_admin' => 'chat.academy-admin',
            'admin' => 'chat.academy-admin', // Super admin uses academy admin view
        ];

        // Get the appropriate view or default to student
        $view = $viewMap[$userType] ?? 'chat.student';

        // Pass the user parameter to the view if it exists
        $viewData = [];
        if ($request->has('user')) {
            $viewData['autoOpenUserId'] = $request->get('user');
        }

        return view($view, $viewData);
    })->name('chat');

    // Temporary debug route for testing message broadcast
    Route::get('/test-broadcast/{userId}', function ($userId) {
        try {
            \Log::info('🧪 Testing broadcast to user: '.$userId);
            $result = \Chatify\Facades\ChatifyMessenger::push('private-chatify.'.$userId, 'messaging', [
                'from_id' => 999,
                'to_id' => $userId,
                'message' => '<div class="test-message">Test broadcast message</div>',
            ]);
            \Log::info('🧪 Broadcast result: '.($result ? 'success' : 'failed'));

            return response()->json(['status' => 'broadcasted', 'result' => $result]);
        } catch (\Exception $e) {
            \Log::error('🧪 Broadcast failed: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()]);
        }
    })->middleware('auth');

});

/*
|--------------------------------------------------------------------------
| LiveKit Webhooks and API Routes
|--------------------------------------------------------------------------
| These routes handle LiveKit webhooks and meeting management API
*/

// LiveKit Webhooks (no authentication required for webhooks from LiveKit server)
Route::prefix('webhooks')->group(function () {
    Route::post('livekit', [\App\Http\Controllers\LiveKitWebhookController::class, 'handleWebhook'])->name('webhooks.livekit');
    Route::get('livekit/health', [\App\Http\Controllers\LiveKitWebhookController::class, 'health'])->name('webhooks.livekit.health');
});

// Meeting API Routes (no separate UI routes)
Route::middleware(['auth'])->group(function () {
    Route::post('meetings/{session}/create-or-get', [\App\Http\Controllers\MeetingController::class, 'createOrGet'])->name('meetings.create-or-get');

    // NO SEPARATE MEETING ROUTES - All meeting functionality is in session pages
});

// LiveKit Meeting API routes (requires authentication)
Route::middleware(['auth'])->prefix('api/meetings')->group(function () {
    Route::post('create', [\App\Http\Controllers\LiveKitMeetingController::class, 'createMeeting'])->name('api.meetings.create');
    Route::get('{sessionId}/token', [\App\Http\Controllers\LiveKitMeetingController::class, 'getParticipantToken'])->name('api.meetings.token');
    Route::post('{sessionId}/recording/start', [\App\Http\Controllers\LiveKitMeetingController::class, 'startRecording'])->name('api.meetings.recording.start');
    Route::post('{sessionId}/recording/stop', [\App\Http\Controllers\LiveKitMeetingController::class, 'stopRecording'])->name('api.meetings.recording.stop');
    Route::get('{sessionId}/info', [\App\Http\Controllers\LiveKitMeetingController::class, 'getRoomInfo'])->name('api.meetings.info');
    Route::post('{sessionId}/end', [\App\Http\Controllers\LiveKitMeetingController::class, 'endMeeting'])->name('api.meetings.end');

    // LiveKit Token API
    Route::post('livekit/token', [\App\Http\Controllers\LiveKitController::class, 'getToken'])->name('api.livekit.token');

    // Meeting Attendance API
    Route::post('attendance/join', [\App\Http\Controllers\MeetingAttendanceController::class, 'recordJoin'])->name('api.meetings.attendance.join');
    Route::post('attendance/leave', [\App\Http\Controllers\MeetingAttendanceController::class, 'recordLeave'])->name('api.meetings.attendance.leave');
    Route::get('attendance/status', [\App\Http\Controllers\MeetingAttendanceController::class, 'getStatus'])->name('api.meetings.attendance.status');
});

// Custom file upload route for Filament components
Route::post('/custom-file-upload', [App\Http\Controllers\CustomFileUploadController::class, 'upload'])->name('custom.file.upload');

// Clean routes - no more test routes needed
