<?php

use App\Http\Controllers\AcademyHomepageController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecordedCourseController;
use App\Http\Controllers\StudentDashboardController;
use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Include authentication routes
require __DIR__.'/auth.php';

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
| Main Domain Routes
|--------------------------------------------------------------------------
*/

// Main domain routes (itqan-platform.test or default academy)
Route::domain(config('app.domain'))->group(function () {

    // Temporary test route for styling verification
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

    Route::get('/', function () {
        // Redirect to a default academy or show available academies
        return redirect('http://itqan-academy.'.config('app.domain'));
    });

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

    // Lesson Viewing & Progress (ID-based)
    Route::get('/courses/{courseId}/lessons/{lessonId}', [LessonController::class, 'show'])->name('lessons.show')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/progress', [LessonController::class, 'updateProgress'])->name('lessons.progress')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [LessonController::class, 'markCompleted'])->name('lessons.complete')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    // Lesson Interactions
    Route::post('/courses/{courseId}/lessons/{lessonId}/bookmark', [LessonController::class, 'addBookmark'])->name('lessons.bookmark')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::delete('/courses/{courseId}/lessons/{lessonId}/bookmark', [LessonController::class, 'removeBookmark'])->name('lessons.unbookmark')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/notes', [LessonController::class, 'addNote'])->name('lessons.notes.add')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/notes', [LessonController::class, 'getNotes'])->name('lessons.notes.get')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/rate', [LessonController::class, 'rate'])->name('lessons.rate')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

    // Lesson Resources
    Route::get('/courses/{courseId}/lessons/{lessonId}/transcript', [LessonController::class, 'getTranscript'])->name('lessons.transcript')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/materials', [LessonController::class, 'downloadMaterials'])->name('lessons.materials')->where(['courseId' => '[0-9]+', 'lessonId' => '[0-9]+']);

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

    // Trial Session Booking (requires auth)
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/quran-teachers/{teacher}/trial', [App\Http\Controllers\PublicQuranTeacherController::class, 'showTrialBooking'])->name('public.quran-teachers.trial');
        Route::post('/quran-teachers/{teacher}/trial', [App\Http\Controllers\PublicQuranTeacherController::class, 'submitTrialRequest'])->name('public.quran-teachers.trial.submit');

        Route::get('/quran-teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicQuranTeacherController::class, 'showSubscriptionBooking'])->name('public.quran-teachers.subscribe');
        Route::post('/quran-teachers/{teacher}/subscribe/{packageId}', [App\Http\Controllers\PublicQuranTeacherController::class, 'submitSubscriptionRequest'])->name('public.quran-teachers.subscribe.submit');

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
    });

    /*
    |--------------------------------------------------------------------------
    | Unified Individual Circles Routes
    |--------------------------------------------------------------------------
    */

    // Unified routes accessible by both teachers and students
    Route::middleware(['auth', 'role:quran_teacher,student'])->group(function () {
        Route::get('/individual-circles/{circle}', [App\Http\Controllers\QuranIndividualCircleController::class, 'show'])->name('individual-circles.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher Individual Circles Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:quran_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        // Individual Circles Management
        Route::get('/individual-circles', [App\Http\Controllers\QuranIndividualCircleController::class, 'index'])->name('individual-circles.index');
        Route::get('/individual-circles/{circle}', [App\Http\Controllers\QuranIndividualCircleController::class, 'show'])->name('individual-circles.show');
        Route::get('/individual-circles/{circle}/progress', [App\Http\Controllers\QuranIndividualCircleController::class, 'progressReport'])->name('individual-circles.progress');

        // AJAX routes for individual circles
        Route::get('/individual-circles/{circle}/template-sessions', [App\Http\Controllers\QuranIndividualCircleController::class, 'getTemplateSessions'])->name('individual-circles.template-sessions');
        Route::put('/individual-circles/{circle}/settings', [App\Http\Controllers\QuranIndividualCircleController::class, 'updateSettings'])->name('individual-circles.update-settings');

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
});

// Custom file upload route for Filament components
Route::post('/custom-file-upload', [App\Http\Controllers\CustomFileUploadController::class, 'upload'])->name('custom.file.upload');
