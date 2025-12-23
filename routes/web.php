<?php

use App\Enums\SessionStatus;
use App\Http\Controllers\AcademyHomepageController;
use App\Http\Controllers\Api\SessionStatusApiController;
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
// LiveKit routes for teacher controls (tenant-aware)
Route::prefix('livekit')->middleware(['auth'])->group(function () {
    // Basic participant endpoints available to authenticated users
    Route::get('participants', [App\Http\Controllers\LiveKitController::class, 'getRoomParticipants']);
    Route::get('rooms/permissions', [App\Http\Controllers\LiveKitController::class, 'getRoomPermissions']);

    // Teacher-only participant control endpoints with detailed debugging
    Route::middleware(['control-participants'])->group(function () {
        Route::post('participants/mute', [App\Http\Controllers\LiveKitController::class, 'muteParticipant']);
        Route::post('participants/mute-all-students', [App\Http\Controllers\LiveKitController::class, 'muteAllStudents']);
        Route::post('participants/disable-all-students-camera', [App\Http\Controllers\LiveKitController::class, 'disableAllStudentsCamera']);
        Route::get('rooms/{room_name}/participants', [App\Http\Controllers\LiveKitController::class, 'getRoomParticipants']);
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

// Session-type-specific status APIs (refactored to controller)
Route::get('/api/academic-sessions/{session}/status', [SessionStatusApiController::class, 'academicSessionStatus'])
    ->name('api.academic-sessions.status');

Route::get('/api/quran-sessions/{session}/status', [SessionStatusApiController::class, 'quranSessionStatus'])
    ->name('api.quran-sessions.status');

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
    // Determine user type
    if ($user->hasRole('quran_teacher')) {
        $userType = 'quran_teacher';
    } elseif ($user->hasRole('academic_teacher')) {
        $userType = 'academic_teacher';
    } else {
        $userType = 'student';
    }

    // Smart session resolution - check all types and find the one that exists
    $academicSession = \App\Models\AcademicSession::find($session);
    $quranSession = \App\Models\QuranSession::find($session);
    $interactiveSession = \App\Models\InteractiveCourseSession::find($session);

    // Use whichever exists (prioritize based on user context)
    if ($interactiveSession) {
        $session = $interactiveSession;
    } elseif ($academicSession && $quranSession) {
        // If both exist with same ID, determine by user context
        if ($user->hasRole('academic_teacher') || $academicSession->student_id === $user->id) {
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
    } elseif ($session instanceof \App\Models\InteractiveCourseSession) {
        // Interactive course sessions use course configuration
        $circle = null;
        $preparationMinutes = $session->course?->preparation_minutes ?? 15;
        $endingBufferMinutes = $session->course?->buffer_minutes ?? 5;
    } else {
        // Quran sessions use circle configuration
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
        $preparationMinutes = $circle?->preparation_minutes ?? 15;
        $endingBufferMinutes = $circle?->ending_buffer_minutes ?? 5;
    }

    // AUTO-COMPLETE: Check if session time + buffer has expired for ongoing/ready sessions
    $now = now();
    $sessionEndTime = null;
    $hasExpired = false;

    if ($session->scheduled_at && in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
        $sessionEndTime = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 60) + $endingBufferMinutes);
        $hasExpired = $now->gte($sessionEndTime);

        if ($hasExpired) {
            // Auto-complete the session
            $session->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => $sessionEndTime,
                'actual_duration_minutes' => $session->duration_minutes ?? 60,
            ]);

            // Close the LiveKit room if it exists
            if ($session->meeting_room_name) {
                try {
                    $liveKitService = app(\App\Services\LiveKitService::class);
                    $liveKitService->endMeeting($session->meeting_room_name);
                    \Log::info('LiveKit room closed on session auto-complete', [
                        'session_id' => $session->id,
                        'room_name' => $session->meeting_room_name,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to close LiveKit room on auto-complete', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            \Log::info('Session auto-completed due to time expiration', [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'scheduled_at' => $session->scheduled_at,
                'session_end_time' => $sessionEndTime,
                'now' => $now,
            ]);
        }
    }

    // Determine if user can join
    // Anyone (students and teachers) can join when session is READY or ONGOING (and not expired)
    $canJoinMeeting = !$hasExpired && in_array($session->status, [
        SessionStatus::READY,
        SessionStatus::ONGOING,
    ]);

    // Allow anyone to join even if marked absent or scheduled (within time window)
    if (in_array($session->status, [
        SessionStatus::ABSENT,
        SessionStatus::SCHEDULED,
    ])) {
        $now = now();
        // Only check timing if session is scheduled
        if ($session->scheduled_at) {
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(($session->duration_minutes ?? 60) + $endingBufferMinutes);

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
            // Anyone can join/start the session
            $message = 'الجلسة جاهزة - يمكنك الانضمام الآن';
            $buttonText = 'انضم للجلسة';
            $buttonClass = 'bg-green-600 hover:bg-green-700';
            break;

        case SessionStatus::ONGOING:
            // Anyone can join the ongoing session
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
                if (in_array($userType, ['quran_teacher', 'academic_teacher'])) {
                    $message = 'الجلسة نشطة - يمكنك بدء أو الانضمام للاجتماع';
                    $buttonText = 'انضم للجلسة';
                    $buttonClass = 'bg-green-600 hover:bg-green-700';
                } else {
                    $message = 'تم تسجيل غيابك ولكن يمكنك الانضمام الآن';
                    $buttonText = 'انضم للجلسة (غائب)';
                    $buttonClass = 'bg-yellow-600 hover:bg-yellow-700';
                }
            } else {
                if (in_array($userType, ['quran_teacher', 'academic_teacher'])) {
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
            'ending_buffer_minutes' => $endingBufferMinutes,
            'meeting_room_name' => $session->meeting_room_name,
            'session_end_time' => $sessionEndTime?->toISOString(),
            'has_expired' => $hasExpired,
        ],
    ]);
})->name('api.sessions.status');

Route::get('/api/sessions/{session}/attendance-status', function (Request $request, $session) {
    $user = $request->user();

    // Smart session resolution - check all types and find the one that exists
    $academicSession = \App\Models\AcademicSession::find($session);
    $quranSession = \App\Models\QuranSession::find($session);
    $interactiveSession = \App\Models\InteractiveCourseSession::find($session);

    // Use whichever exists (prioritize based on user context)
    if ($interactiveSession) {
        $session = $interactiveSession;
    } elseif ($academicSession && $quranSession) {
        // If both exist with same ID, determine by user context
        if ($user->hasRole('academic_teacher') || $academicSession->student_id === $user->id) {
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
        } elseif ($session instanceof \App\Models\InteractiveCourseSession) {
            $sessionReport = \App\Models\InteractiveSessionReport::where('session_id', $session->id)
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
        } elseif ($session instanceof \App\Models\InteractiveCourseSession) {
            $unifiedService = app(\App\Services\UnifiedAttendanceService::class);
            $status = $unifiedService->getCurrentAttendanceStatus($session, $user);
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

// Debug routes - ONLY available in local/testing environments
if (app()->environment('local', 'testing')) {
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
}

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

    // Static Pages
    Route::get('/terms', [App\Http\Controllers\StaticPageController::class, 'terms'])->name('academy.terms');
    Route::get('/refund-policy', [App\Http\Controllers\StaticPageController::class, 'refundPolicy'])->name('academy.refund-policy');
    Route::get('/privacy-policy', [App\Http\Controllers\StaticPageController::class, 'privacyPolicy'])->name('academy.privacy-policy');
    Route::get('/about-us', [App\Http\Controllers\StaticPageController::class, 'aboutUs'])->name('academy.about-us');

    /*
    |--------------------------------------------------------------------------
    | Course Management Routes
    |--------------------------------------------------------------------------
    */

    // Redirect old /courses GET route to new /my-courses route
    Route::get('/courses', function () {
        if (auth()->check()) {
            $academy = auth()->user()->academy;
            return redirect()->route('courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']);
        }
        // For guest users, redirect to login
        return redirect()->route('login');
    })->name('courses.redirect');

    // Course Management (Admin/Teacher Only)
    Route::middleware(['auth', 'role:admin,teacher,quran_teacher,academic_teacher'])->group(function () {
        Route::get('/courses/create', [RecordedCourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [RecordedCourseController::class, 'store'])->name('courses.store');

        // Certificate Preview (Teachers/Admins) - accepts GET for iframe and POST for form
        Route::match(['get', 'post'], '/certificates/preview', [\App\Http\Controllers\CertificateController::class, 'preview'])->name('certificates.preview');
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

        // Notifications page (available for all authenticated users)
        Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');

        // Notification API endpoints
        Route::post('/api/notifications/{id}/mark-as-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::post('/api/notifications/mark-all-as-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
        Route::delete('/api/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
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

    // Payment Flow (new gateway system)
    Route::post('/payments/{payment}/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
    Route::get('/payments/{payment}/callback', [\App\Http\Controllers\PaymobWebhookController::class, 'callback'])->name('payments.callback');

    // Payment Methods API
    Route::get('/api/payment-methods/{academy}', [PaymentController::class, 'getPaymentMethods'])->name('api.payment-methods');

    /*
    |--------------------------------------------------------------------------
    | Student Certificate Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->group(function () {
        // Certificates
        Route::get('/certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->name('student.certificates');
        Route::get('/certificates/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('student.certificate.download');
        Route::get('/certificates/{certificate}/view', [\App\Http\Controllers\CertificateController::class, 'view'])->name('student.certificate.view');
        Route::post('/certificates/request-interactive', [\App\Http\Controllers\CertificateController::class, 'requestForInteractiveCourse'])->name('student.certificate.request-interactive');
    });

    /*
    |--------------------------------------------------------------------------
    | Student Profile Routes
    |--------------------------------------------------------------------------
    | Note: Some routes defined here due to route registration issues in auth.php
    */

    // Missing student routes that weren't registering from auth.php
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/profile', [App\Http\Controllers\StudentProfileController::class, 'index'])->name('student.profile');
        Route::get('/search', [App\Http\Controllers\StudentProfileController::class, 'search'])->name('student.search');

        // 301 Permanent Redirects - OLD student routes to NEW unified routes
        Route::permanentRedirect('/my-quran-teachers', '/quran-teachers');
        Route::permanentRedirect('/my-quran-circles', '/quran-circles');
        Route::permanentRedirect('/my-academic-teachers', '/academic-teachers');
        Route::permanentRedirect('/my-interactive-courses', '/interactive-courses');

        Route::get('/payments', [App\Http\Controllers\StudentProfileController::class, 'payments'])->name('student.payments');
        Route::get('/subscriptions', [App\Http\Controllers\StudentProfileController::class, 'subscriptions'])->name('student.subscriptions');
        Route::patch('/subscriptions/{type}/{id}/toggle-auto-renew', [App\Http\Controllers\StudentProfileController::class, 'toggleAutoRenew'])->name('student.subscriptions.toggle-auto-renew');
        Route::patch('/subscriptions/{type}/{id}/cancel', [App\Http\Controllers\StudentProfileController::class, 'cancelSubscription'])->name('student.subscriptions.cancel');
        // Note: /courses route moved to public section for unified public/authenticated access

        // Student session routes (moved from auth.php for subdomain compatibility)
        Route::get('/sessions/{sessionId}', [App\Http\Controllers\QuranSessionController::class, 'showForStudent'])->name('student.sessions.show');
        Route::put('/sessions/{sessionId}/feedback', [App\Http\Controllers\QuranSessionController::class, 'addFeedback'])->name('student.sessions.feedback');

        // Academic subscription routes for students
        Route::get('/academic-subscriptions/{subscriptionId}', [App\Http\Controllers\StudentProfileController::class, 'showAcademicSubscription'])->name('student.academic-subscriptions.show');
        Route::get('/academic-subscriptions/{subscription}/report', [App\Http\Controllers\AcademicSessionController::class, 'studentSubscriptionReport'])->name('student.academic-subscriptions.report');

        // Academic session routes for students
        Route::get('/academic-sessions/{session}', [App\Http\Controllers\StudentProfileController::class, 'showAcademicSession'])->name('student.academic-sessions.show');
        Route::put('/academic-sessions/{session}/feedback', [App\Http\Controllers\AcademicSessionController::class, 'addStudentFeedback'])->name('student.academic-sessions.feedback');
        Route::post('/academic-sessions/{session}/submit-homework', [App\Http\Controllers\AcademicSessionController::class, 'submitHomework'])->name('student.academic-sessions.submit-homework');

        // Homework routes for students
        Route::prefix('homework')->name('student.homework.')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\HomeworkController::class, 'index'])->name('index');
            Route::get('/{id}/{type}/submit', [App\Http\Controllers\Student\HomeworkController::class, 'submit'])->name('submit');
            Route::post('/{id}/{type}/submit', [App\Http\Controllers\Student\HomeworkController::class, 'submitProcess'])->name('submit.process');
            Route::get('/{id}/{type}/view', [App\Http\Controllers\Student\HomeworkController::class, 'view'])->name('view');
        });

        // Circle Report routes for students
        Route::get('/individual-circles/{circle}/report', [App\Http\Controllers\Student\CircleReportController::class, 'showIndividual'])->name('student.individual-circles.report');
        Route::get('/group-circles/{circle}/report', [App\Http\Controllers\Student\CircleReportController::class, 'showGroup'])->name('student.group-circles.report');

        // Quiz routes
        Route::get('/quizzes', function ($subdomain) {
            return app(\App\Http\Controllers\QuizController::class)->index();
        })->name('student.quizzes');

        Route::get('/student-quiz-start/{quiz_id}', function ($subdomain, $quiz_id) {
            \Log::info('Quiz start route reached (closure wrapper)', ['subdomain' => $subdomain, 'quiz_id' => $quiz_id, 'user_id' => auth()->id()]);
            return app(\App\Http\Controllers\QuizController::class)->start($quiz_id);
        })->name('student.quiz.start');

        Route::get('/student-quiz-take/{attempt_id}', function ($subdomain, $attempt_id) {
            \Log::info('Quiz take route reached', ['subdomain' => $subdomain, 'attempt_id' => $attempt_id]);
            return app(\App\Http\Controllers\QuizController::class)->take($attempt_id);
        })->name('student.quiz.take');

        Route::post('/student-quiz-submit/{attempt_id}', function (\Illuminate\Http\Request $request, $subdomain, $attempt_id) {
            \Log::info('Quiz submit route reached', ['subdomain' => $subdomain, 'attempt_id' => $attempt_id]);
            return app(\App\Http\Controllers\QuizController::class)->submit($request, $attempt_id);
        })->name('student.quiz.submit');

        Route::get('/student-quiz-results/{quiz_id}', function ($subdomain, $quiz_id) {
            \Log::info('Quiz results route reached', ['subdomain' => $subdomain, 'quiz_id' => $quiz_id]);
            return app(\App\Http\Controllers\QuizController::class)->result($quiz_id);
        })->name('student.quiz.result');
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
    | Unified Quran Teacher Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Quran Teachers Listing (works for both public and authenticated)
    Route::get('/quran-teachers', [App\Http\Controllers\UnifiedQuranTeacherController::class, 'index'])->name('quran-teachers.index');

    // UNIFIED Individual Teacher Profile Pages
    Route::get('/quran-teachers/{teacherId}', [App\Http\Controllers\UnifiedQuranTeacherController::class, 'show'])->name('quran-teachers.show');

    // Backward compatibility: Redirect old public route names
    // (The URL is the same, just route name changes for consistency)

    /*
    |--------------------------------------------------------------------------
    | Unified Academic Teacher Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Academic Teachers Listing (works for both public and authenticated)
    Route::get('/academic-teachers', [App\Http\Controllers\UnifiedAcademicTeacherController::class, 'index'])->name('academic-teachers.index');

    // UNIFIED Individual Academic Teacher Profile Pages
    Route::get('/academic-teachers/{teacherId}', [App\Http\Controllers\UnifiedAcademicTeacherController::class, 'show'])->name('academic-teachers.show');

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

    // Trial Session Booking (requires auth) - UNIFIED
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::post('/quran-teachers/{teacherId}/trial', [App\Http\Controllers\UnifiedQuranTeacherController::class, 'submitTrialRequest'])->name('quran-teachers.trial.submit');

        // Quran Teacher Subscription Booking
        Route::get('/quran-teachers/{teacherId}/subscribe/{packageId}', [App\Http\Controllers\UnifiedQuranTeacherController::class, 'showSubscriptionBooking'])->name('quran-teachers.subscribe');
        Route::post('/quran-teachers/{teacherId}/subscribe/{packageId}', [App\Http\Controllers\UnifiedQuranTeacherController::class, 'submitSubscriptionRequest'])->name('quran-teachers.subscribe.submit');

        // Academic Package Subscription
        Route::get('/academic-packages/teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicAcademicPackageController::class, 'showSubscriptionForm'])->name('public.academic-packages.subscribe');
        Route::post('/academic-packages/teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicAcademicPackageController::class, 'submitSubscriptionRequest'])->name('public.academic-packages.subscribe.submit');

        // Note: Student academic session route moved to line 1195 with other student routes

        // Quran Subscription Payment
        Route::get('/quran/subscription/{subscription}/payment', [App\Http\Controllers\QuranSubscriptionPaymentController::class, 'create'])->name('quran.subscription.payment');
        Route::post('/quran/subscription/{subscription}/payment', [App\Http\Controllers\QuranSubscriptionPaymentController::class, 'store'])->name('quran.subscription.payment.submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Unified Quran Circle Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Quran Circles Listing (works for both public and authenticated)
    Route::get('/quran-circles', [App\Http\Controllers\UnifiedQuranCircleController::class, 'index'])->name('quran-circles.index');

    // UNIFIED Individual Circle Details Pages
    Route::get('/quran-circles/{circleId}', [App\Http\Controllers\UnifiedQuranCircleController::class, 'show'])->name('quran-circles.show');

    // Circle Enrollment (requires auth) - UNIFIED
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::post('/quran-circles/{circleId}/enroll', [App\Http\Controllers\UnifiedQuranCircleController::class, 'enroll'])->name('quran-circles.enroll');
    });

    /*
    |--------------------------------------------------------------------------
    | Unified Interactive Courses Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Interactive Courses Listing (works for both public and authenticated)
    Route::get('/interactive-courses', [App\Http\Controllers\UnifiedInteractiveCourseController::class, 'index'])->name('interactive-courses.index');

    // UNIFIED Individual Interactive Course Details
    Route::get('/interactive-courses/{courseId}', [App\Http\Controllers\UnifiedInteractiveCourseController::class, 'show'])->name('interactive-courses.show');

    // Interactive Course Enrollment (requires authentication) - UNIFIED
    Route::middleware(['auth'])->group(function () {
        Route::post('/interactive-courses/{courseId}/enroll', [App\Http\Controllers\UnifiedInteractiveCourseController::class, 'enroll'])->name('interactive-courses.enroll');
    });

    /*
    |--------------------------------------------------------------------------
    | Public Recorded Courses Routes (Unified)
    |--------------------------------------------------------------------------
    */

    // Unified Recorded Courses Listing (works for both public and authenticated users)
    Route::get('/courses', [RecordedCourseController::class, 'index'])->name('courses.index');

    /*
    |--------------------------------------------------------------------------
    | Teacher Calendar Routes - REMOVED
    |--------------------------------------------------------------------------
    | Frontend teacher calendar has been removed.
    | Use Filament dashboard calendar instead at /teacher-panel or /academic-teacher-panel
    */

    // Homework grading routes for teachers
    Route::middleware(['auth', 'role:quran_teacher,academic_teacher'])->group(function () {
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
    | Teacher Academic Session Routes
    |--------------------------------------------------------------------------
    | Consolidated routes for academic teachers to manage individual lessons
    */
    Route::middleware(['auth', 'role:academic_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        // NOTE: Academic lessons routes are defined in auth.php under teacher.academic.* prefix
        // (teacher/academic/lessons routes with progress and update-settings)

        // Academic sessions list
        Route::get('/academic-sessions', [App\Http\Controllers\AcademicSessionController::class, 'index'])->name('academic-sessions.index');

        // Academic subscription comprehensive report
        Route::get('/academic-subscriptions/{subscription}/report', [App\Http\Controllers\AcademicSessionController::class, 'subscriptionReport'])->name('academic-subscriptions.report');

        Route::prefix('academic-sessions/{session}')->name('academic-sessions.')->group(function () {
            // Session view (moved from auth.php for consistency)
            Route::get('/', [App\Http\Controllers\AcademicSessionController::class, 'show'])->name('show');

            // Session management
            Route::put('/evaluation', [App\Http\Controllers\AcademicSessionController::class, 'updateEvaluation'])->name('evaluation');
            Route::put('/status', [App\Http\Controllers\AcademicSessionController::class, 'updateStatus'])->name('status');
            Route::put('/reschedule', [App\Http\Controllers\AcademicSessionController::class, 'reschedule'])->name('reschedule');
            Route::put('/cancel', [App\Http\Controllers\AcademicSessionController::class, 'cancel'])->name('cancel');

            // Homework management
            Route::post('/homework/assign', [App\Http\Controllers\AcademicSessionController::class, 'assignHomework'])->name('assign-homework');
            Route::put('/homework/update', [App\Http\Controllers\AcademicSessionController::class, 'updateHomework'])->name('update-homework');
            Route::post('/reports/{reportId}/homework/grade', [App\Http\Controllers\AcademicSessionController::class, 'gradeHomework'])->name('grade-homework');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher Interactive Course Session Routes
    |--------------------------------------------------------------------------
    | Routes for academic teachers to manage interactive course sessions
    */
    Route::middleware(['auth', 'role:academic_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        // Interactive courses listing for teachers
        Route::get('/interactive-courses', [App\Http\Controllers\AcademicIndividualLessonController::class, 'interactiveCoursesIndex'])->name('interactive-courses.index');

        // Interactive course comprehensive report
        Route::get('/interactive-courses/{course}/report', [App\Http\Controllers\StudentProfileController::class, 'interactiveCourseReport'])->name('interactive-courses.report');
        // Interactive course individual student report
        Route::get('/interactive-courses/{course}/students/{student}/report', [App\Http\Controllers\StudentProfileController::class, 'interactiveCourseStudentReport'])->name('interactive-courses.student-report');

        Route::prefix('interactive-sessions/{session}')->name('interactive-sessions.')->group(function () {
            // Session view for teachers
            Route::get('/', [App\Http\Controllers\StudentProfileController::class, 'showInteractiveCourseSession'])->name('show');
            // Update session content
            Route::put('/content', [App\Http\Controllers\StudentProfileController::class, 'updateInteractiveSessionContent'])->name('content');
            // Assign homework
            Route::post('/assign-homework', [App\Http\Controllers\StudentProfileController::class, 'assignInteractiveSessionHomework'])->name('assign-homework');
            // Update homework
            Route::put('/update-homework', [App\Http\Controllers\StudentProfileController::class, 'updateInteractiveSessionHomework'])->name('update-homework');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher Student Report Management Routes (AJAX)
    |--------------------------------------------------------------------------
    | Unified routes for creating and updating student reports across all session types
    */
    // Quran teacher reports - specific route
    Route::middleware(['auth', 'role:quran_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::post('/quran-reports/{type}', [App\Http\Controllers\StudentReportController::class, 'store'])->name('quran-reports.store');
        Route::put('/quran-reports/{type}/{report}', [App\Http\Controllers\StudentReportController::class, 'update'])->name('quran-reports.update');
    });

    // Academic teacher reports - specific route
    Route::middleware(['auth', 'role:academic_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::post('/academic-reports/{type}', [App\Http\Controllers\StudentReportController::class, 'store'])->name('academic-reports.store');
        Route::put('/academic-reports/{type}/{report}', [App\Http\Controllers\StudentReportController::class, 'update'])->name('academic-reports.update');
    });

    // Legacy unified route (keeping for backwards compatibility)
    Route::middleware(['auth', 'role:academic_teacher,quran_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        // Create new report
        Route::post('/reports/{type}', [App\Http\Controllers\StudentReportController::class, 'store'])->name('reports.store');
        // Update existing report
        Route::put('/reports/{type}/{report}', [App\Http\Controllers\StudentReportController::class, 'update'])->name('reports.update');
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
        Route::get('/individual-circles/{circle}/report', [App\Http\Controllers\Teacher\IndividualCircleReportController::class, 'show'])->name('individual-circles.report');

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

        // Group Circle Reports
        Route::get('/group-circles/{circle}/report', [App\Http\Controllers\Teacher\GroupCircleReportController::class, 'show'])->name('group-circles.report');
        Route::get('/group-circles/{circle}/students/{student}/report', [App\Http\Controllers\Teacher\GroupCircleReportController::class, 'studentReport'])->name('group-circles.student-report');

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
    | Authenticated students accessing these routes will see their personalized views
    */

    // 301 Redirect - OLD interactive course detail to NEW unified route
    Route::permanentRedirect('/my-interactive-courses/{course}', '/interactive-courses/{course}');

    // Interactive course session detail - for enrolled students
    Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/interactive-sessions/{session}', [App\Http\Controllers\StudentProfileController::class, 'showInteractiveCourseSession'])->name('interactive-sessions.show');
        Route::post('/interactive-sessions/{session}/feedback', [App\Http\Controllers\StudentProfileController::class, 'addInteractiveSessionFeedback'])->name('interactive-sessions.feedback');
        Route::post('/interactive-sessions/{session}/homework', [App\Http\Controllers\StudentProfileController::class, 'submitInteractiveCourseHomework'])->name('interactive-sessions.homework');
        // Interactive course report - for enrolled students
        Route::get('/interactive-courses/{course}/report', [App\Http\Controllers\StudentProfileController::class, 'studentInteractiveCourseReport'])->name('interactive-courses.report');
    });

    /*
    |--------------------------------------------------------------------------
    | Chat Routes (WireChat)
    |--------------------------------------------------------------------------
    | Override WireChat package routes to provide Arabic titles and subdomain support
    */
    Route::middleware(config('wirechat.routes.middleware'))
        ->prefix(config('wirechat.routes.prefix'))
        ->group(function () {
            Route::get('/', \App\Livewire\Pages\Chats::class)->name('chats');
            Route::get('/start-with/{user}', function ($subdomain, \App\Models\User $user) {
                // Log the attempt for debugging
                \Log::info('Chat start-with route called', [
                    'subdomain' => $subdomain,
                    'auth_user_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'target_user_name' => $user->name,
                ]);

                // Get or create conversation with the specified user
                $conversation = auth()->user()->getOrCreatePrivateConversation($user);

                if (!$conversation) {
                    \Log::error('Failed to create conversation in route', [
                        'auth_user_id' => auth()->id(),
                        'target_user_id' => $user->id,
                    ]);
                    // If conversation creation fails, redirect to chats list with error
                    return redirect()->route('chats', ['subdomain' => $subdomain])
                        ->with('error', 'حدث خطأ في إنشاء المحادثة. يرجى المحاولة لاحقاً.');
                }

                \Log::info('Conversation created/found successfully', [
                    'conversation_id' => $conversation->id,
                ]);

                return redirect()->route('chat', [
                    'subdomain' => $subdomain,
                    'conversation' => $conversation->id
                ]);
            })->name('chat.start-with');
            Route::get('/{conversation}', \App\Livewire\Pages\Chat::class)->middleware('belongsToConversation')->name('chat');
        });

    // OLD: Chatify test routes - DISABLED
    // Route::get('/test-broadcast/{userId}', function ($userId) {
    //     // Disabled - using WireChat now
    // })->middleware('auth');

    /*
    |--------------------------------------------------------------------------
    | Parent Routes
    |--------------------------------------------------------------------------
    | Parent portal routes for viewing children's data (subscriptions,
    | sessions, payments, certificates, reports). Uses child-switching pattern.
    */

    Route::middleware(['auth', 'role:parent', 'child.selection'])->prefix('parent')->name('parent.')->group(function () {

        // Child Selection API (for top bar selector)
        Route::post('/select-child', [\App\Http\Controllers\ParentDashboardController::class, 'selectChildSession'])->name('select-child');

        // Profile (Main Dashboard - Profile is now the main page)
        Route::get('/', [\App\Http\Controllers\ParentProfileController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\ParentProfileController::class, 'index'])->name('profile');
        Route::get('/profile/edit', [\App\Http\Controllers\ParentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\ParentProfileController::class, 'update'])->name('profile.update');

        // Children Management
        Route::prefix('children')->name('children.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentChildrenController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\ParentChildrenController::class, 'store'])->name('store');
            Route::delete('/{student}', [\App\Http\Controllers\ParentChildrenController::class, 'destroy'])->name('destroy');
        });

        // Sessions
        Route::prefix('sessions')->name('sessions.')->group(function () {
            Route::get('/upcoming', [\App\Http\Controllers\ParentSessionController::class, 'upcoming'])->name('upcoming');
            Route::get('/history', [\App\Http\Controllers\ParentSessionController::class, 'history'])->name('history');
            Route::get('/{sessionType}/{session}', [\App\Http\Controllers\ParentSessionController::class, 'show'])->name('show');
        });

        // Calendar
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentCalendarController::class, 'index'])->name('index');
            Route::get('/events', [\App\Http\Controllers\ParentCalendarController::class, 'getEvents'])->name('events');
        });

        // Subscriptions
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentSubscriptionController::class, 'index'])->name('index');
            Route::get('/{type}/{subscription}', [\App\Http\Controllers\ParentSubscriptionController::class, 'show'])->name('show');
        });

        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentPaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [\App\Http\Controllers\ParentPaymentController::class, 'show'])->name('show');
            Route::get('/{payment}/receipt', [\App\Http\Controllers\ParentPaymentController::class, 'downloadReceipt'])->name('receipt');
        });

        // Certificates
        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentCertificateController::class, 'index'])->name('index');
            Route::get('/{certificate}', [\App\Http\Controllers\ParentCertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/download', [\App\Http\Controllers\ParentCertificateController::class, 'download'])->name('download');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/progress', [\App\Http\Controllers\ParentReportController::class, 'progressReport'])->name('progress');
            // Redirect old attendance route to unified progress report
            Route::get('/attendance', fn($subdomain) => redirect()->route('parent.reports.progress', ['subdomain' => $subdomain]))->name('attendance');

            // Detailed reports for individual subscriptions
            Route::get('/quran/individual/{circle}', [\App\Http\Controllers\ParentReportController::class, 'quranIndividualReport'])->name('quran.individual');
            Route::get('/academic/{subscription}', [\App\Http\Controllers\ParentReportController::class, 'academicSubscriptionReport'])->name('academic.subscription');
            Route::get('/interactive/{course}', [\App\Http\Controllers\ParentReportController::class, 'interactiveCourseReport'])->name('interactive.course');
        });

        // Homework (reuses student views with parent layout)
        Route::prefix('homework')->name('homework.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentHomeworkController::class, 'index'])->name('index');
            Route::get('/{id}/{type?}', [\App\Http\Controllers\ParentHomeworkController::class, 'view'])->name('view');
        });

        // Quizzes
        Route::prefix('quizzes')->name('quizzes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ParentQuizController::class, 'index'])->name('index');
            Route::get('/{quiz}/result', [\App\Http\Controllers\ParentQuizController::class, 'result'])->name('result');
        });
    });

});

/*
|--------------------------------------------------------------------------
| LiveKit Webhooks and API Routes
|--------------------------------------------------------------------------
| These routes handle LiveKit webhooks and meeting management API
*/

// Webhooks (no authentication required - validated via signatures)
// Rate limited to prevent abuse, CSRF excluded since webhooks use signature validation
Route::prefix('webhooks')->middleware(['throttle:60,1'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    // LiveKit webhooks
    Route::post('livekit', [\App\Http\Controllers\LiveKitWebhookController::class, 'handleWebhook'])->name('webhooks.livekit');
    Route::get('livekit/health', [\App\Http\Controllers\LiveKitWebhookController::class, 'health'])->name('webhooks.livekit.health');

    // Payment gateway webhooks (validated via HMAC)
    Route::post('paymob', [\App\Http\Controllers\PaymobWebhookController::class, 'handle'])->name('webhooks.paymob');
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
    Route::get('{sessionId}/info', [\App\Http\Controllers\LiveKitMeetingController::class, 'getRoomInfo'])->name('api.meetings.info');
    Route::post('{sessionId}/end', [\App\Http\Controllers\LiveKitMeetingController::class, 'endMeeting'])->name('api.meetings.end');

    // LiveKit Token API
    Route::post('livekit/token', [\App\Http\Controllers\LiveKitController::class, 'getToken'])->name('api.livekit.token');

    // Meeting Attendance API - DEPRECATED: Now using webhook-based attendance
    // Route::post('attendance/join', [\App\Http\Controllers\MeetingAttendanceController::class, 'recordJoin'])->name('api.meetings.attendance.join');
    // Route::post('attendance/leave', [\App\Http\Controllers\MeetingAttendanceController::class, 'recordLeave'])->name('api.meetings.attendance.leave');
    // Route::get('attendance/status', [\App\Http\Controllers\MeetingAttendanceController::class, 'getStatus'])->name('api.meetings.attendance.status');
});

// Interactive Course Recording API routes (requires authentication)
Route::middleware(['auth'])->prefix('api/recordings')->group(function () {
    // Recording control (start/stop)
    Route::post('start', [\App\Http\Controllers\InteractiveCourseRecordingController::class, 'startRecording'])->name('api.recordings.start');
    Route::post('stop', [\App\Http\Controllers\InteractiveCourseRecordingController::class, 'stopRecording'])->name('api.recordings.stop');

    // Recording management
    Route::get('session/{sessionId}', [\App\Http\Controllers\InteractiveCourseRecordingController::class, 'getSessionRecordings'])->name('api.recordings.session');
    Route::delete('{recordingId}', [\App\Http\Controllers\InteractiveCourseRecordingController::class, 'deleteRecording'])->name('api.recordings.delete');

    // Recording access (download/stream)
    Route::get('{recordingId}/download', [\App\Http\Controllers\InteractiveCourseRecordingController::class, 'downloadRecording'])->name('recordings.download');
    Route::get('{recordingId}/stream', [\App\Http\Controllers\InteractiveCourseRecordingController::class, 'streamRecording'])->name('recordings.stream');
});

// Custom file upload route for Filament components (requires authentication)
Route::middleware(['auth'])->post('/custom-file-upload', [App\Http\Controllers\CustomFileUploadController::class, 'upload'])->name('custom.file.upload');

// Clean routes - no more test routes needed

/*
|--------------------------------------------------------------------------
| Certificate Template Preview (Development Only)
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    // HTML preview (browser) - for layout testing
    Route::get('/dev/certificate-preview', function () {
        $data = [
            'student_name' => 'اسم الطالب التجريبي',
            'certificate_text' => 'لقد أتم الطالب حفظ الجزء الأول من القرآن الكريم بإتقان وتجويد ممتاز. نسأل الله أن يبارك فيه ويجعله من حفظة كتابه.',
            'certificate_number' => 'CERT-2024-001',
            'issued_date_formatted' => now()->format('Y/m/d'),
            'teacher_name' => 'أ. محمد المعلم',
            'academy_name' => 'أكاديمية إتقان',
            'academy_logo' => null,
            'template_style' => \App\Enums\CertificateTemplateStyle::TEMPLATE_1,
        ];
        return view('pdf.certificates.png-template', $data);
    })->name('dev.certificate-preview');

    // PDF preview (download/stream) - using TCPDF for Arabic support
    Route::get('/dev/certificate-pdf-preview', function () {
        $data = [
            'student_name' => 'اسم الطالب التجريبي',
            'certificate_text' => 'لقد أتم الطالب حفظ الجزء الأول من القرآن الكريم بإتقان وتجويد ممتاز. نسأل الله أن يبارك فيه ويجعله من حفظة كتابه.',
            'certificate_number' => 'CERT-2024-001',
            'issued_date_formatted' => now()->format('Y/m/d'),
            'teacher_name' => 'أ. محمد المعلم',
            'academy_name' => 'أكاديمية إتقان',
            'academy_logo' => null,
            'template_style' => \App\Enums\CertificateTemplateStyle::TEMPLATE_1,
        ];

        $certificateService = app(\App\Services\CertificateService::class);
        $pdf = $certificateService->previewCertificate($data, $data['template_style']);

        return response($pdf->Output('', 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="certificate-preview.pdf"');
    })->name('dev.certificate-pdf-preview');
}

// Chat routes moved to subdomain group (see line ~1637)
