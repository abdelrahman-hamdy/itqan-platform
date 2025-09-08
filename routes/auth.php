<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Main domain authentication routes (for Super-Admin)
// Note: Filament handles /admin/login automatically, so we don't need custom routes for admin panel

// Subdomain authentication routes
Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Login Routes (Unified for all roles)
    |--------------------------------------------------------------------------
    */

    // Unified login page for all roles
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Student Registration Routes
    |--------------------------------------------------------------------------
    */

    // Student registration (public access)
    Route::get('/register', [AuthController::class, 'showStudentRegistration'])->name('student.register');
    Route::post('/register', [AuthController::class, 'registerStudent'])->name('student.register.post');

    /*
    |--------------------------------------------------------------------------
    | Teacher Registration Routes
    |--------------------------------------------------------------------------
    */

    // Teacher registration (public access)
    Route::get('/teacher/register', [AuthController::class, 'showTeacherRegistration'])->name('teacher.register');
    Route::post('/teacher/register/step1', [AuthController::class, 'registerTeacherStep1'])->name('teacher.register.step1');
    Route::get('/teacher/register/step2', [AuthController::class, 'showTeacherRegistrationStep2'])->name('teacher.register.step2');
    Route::post('/teacher/register/step2', [AuthController::class, 'registerTeacherStep2'])->name('teacher.register.step2.post');
    Route::get('/teacher/register/success', [AuthController::class, 'showTeacherRegistrationSuccess'])->name('teacher.register.success');

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Require Authentication)
    |--------------------------------------------------------------------------
    */

    // Student routes (profile-based, no dashboard)
    // Note: Main student routes moved to web.php due to registration issues
    Route::middleware(['auth', 'role:student'])->group(function () {
        // Only non-conflicting routes remain here
        Route::get('/profile/edit', [App\Http\Controllers\StudentProfileController::class, 'edit'])->name('student.profile.edit');
        Route::put('/profile/update', [App\Http\Controllers\StudentProfileController::class, 'update'])->name('student.profile.update');

        Route::get('/subscriptions', [App\Http\Controllers\StudentProfileController::class, 'subscriptions'])->name('student.subscriptions');
        Route::get('/progress', [App\Http\Controllers\StudentProfileController::class, 'progress'])->name('student.progress');
        Route::get('/certificates', [App\Http\Controllers\StudentProfileController::class, 'certificates'])->name('student.certificates');
        Route::get('/quran', [App\Http\Controllers\StudentProfileController::class, 'quranProfile'])->name('student.quran');

        // Interactive courses routes moved to web.php for proper ordering
        // Academic teachers route moved to web.php for subdomain compatibility

        // Note: student.courses route is now handled in web.php

        Route::get('/my-assignments', function () {
            return view('student.assignments');
        })->name('student.assignments');

        // Quran sessions management moved to individual circles

        // Course detail pages - REMOVED: Moved to main web.php with ID-based routing
        // Route::get('/courses/{course}', [App\Http\Controllers\StudentProfileController::class, 'courseShow'])->name('student.courses.show');

        // Certificate downloads
        Route::get('/certificates/{enrollment}/download', [App\Http\Controllers\StudentProfileController::class, 'downloadCertificate'])->name('student.certificates.download');

        // Quran circle management
        Route::get('/circles/{circleId}', [App\Http\Controllers\StudentProfileController::class, 'showCircle'])->name('student.circles.show');
        Route::post('/circles/{circleId}/enroll', [App\Http\Controllers\StudentProfileController::class, 'enrollInCircle'])->name('student.circles.enroll');
        Route::post('/circles/{circleId}/leave', [App\Http\Controllers\StudentProfileController::class, 'leaveCircle'])->name('student.circles.leave');

        // Individual circles management - MOVED to web.php unified route

        // Session routes for students - MOVED to web.php for subdomain compatibility
    });

    // Parent routes (profile-based, no dashboard)
    Route::middleware(['auth', 'role:parent'])->group(function () {
        Route::get('/profile', function () {
            return view('parent.profile');
        })->name('parent.profile');

        Route::get('/my-children', function () {
            return view('parent.children');
        })->name('parent.children');

        Route::get('/payments', function () {
            return view('parent.payments');
        })->name('parent.payments');

        Route::get('/reports', function () {
            return view('parent.reports');
        })->name('parent.reports');
    });

    // Academy Admin routes (dashboard access)
    Route::middleware(['auth', 'role:academy_admin'])->group(function () {
        Route::get('/panel', function () {
            return redirect('/panel/dashboard');
        })->name('academy.admin.dashboard');
    });

    // Teacher routes (profile-based with dashboard access)
    Route::middleware(['auth', 'role:teacher,quran_teacher,academic_teacher'])->prefix('teacher')->group(function () {
        // Main teacher route with smart dashboard redirect
        Route::get('/', function () {
            $user = Auth::user();
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            // Smart redirect based on teacher type
            if ($user->isAcademicTeacher()) {
                return redirect('/academic-teacher-panel');
            } elseif ($user->isQuranTeacher()) {
                return redirect('/teacher-panel');
            }

            // Fallback to profile for other teacher types
            return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
        })->name('teacher.dashboard');

        // Direct panel access redirect (for when users bookmark panel URLs)
        Route::get('/panel-redirect', function () {
            $user = Auth::user();
            $subdomain = request()->route('subdomain') ?? 'itqan-academy';

            if ($user->isAcademicTeacher()) {
                return redirect('/academic-teacher-panel');
            } elseif ($user->isQuranTeacher()) {
                return redirect('/teacher-panel');
            }

            return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
        })->name('teacher.panel.redirect');

        // Teacher profile routes
        Route::get('/profile', [App\Http\Controllers\TeacherProfileController::class, 'index'])->name('teacher.profile');
        Route::get('/profile/edit', [App\Http\Controllers\TeacherProfileController::class, 'edit'])->name('teacher.profile.edit');
        Route::put('/profile/update', [App\Http\Controllers\TeacherProfileController::class, 'update'])->name('teacher.profile.update');
        Route::get('/earnings', [App\Http\Controllers\TeacherProfileController::class, 'earnings'])->name('teacher.earnings');
        Route::get('/schedule', [App\Http\Controllers\TeacherProfileController::class, 'schedule'])->name('teacher.schedule');
        Route::get('/students', [App\Http\Controllers\TeacherProfileController::class, 'students'])->name('teacher.students');
        Route::get('/students/{student}', [App\Http\Controllers\TeacherProfileController::class, 'showStudent'])->name('teacher.students.show');

        // Enhanced Schedule Management Routes
        Route::prefix('schedule')->name('teacher.schedule.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\TeacherScheduleController::class, 'index'])->name('dashboard');
            Route::put('/availability', [App\Http\Controllers\TeacherScheduleController::class, 'updateAvailability'])->name('availability.update');

            // Trial Session Scheduling
            Route::get('/trial/{trialRequest}', [App\Http\Controllers\TeacherScheduleController::class, 'showTrialScheduling'])->name('trial.show');
            Route::post('/trial/{trialRequest}', [App\Http\Controllers\TeacherScheduleController::class, 'scheduleTrialSession'])->name('trial.schedule');
            Route::post('/trial/{trialRequest}/approve', [App\Http\Controllers\TeacherScheduleController::class, 'approveTrialRequest'])->name('trial.approve');
            Route::post('/trial/{trialRequest}/reject', [App\Http\Controllers\TeacherScheduleController::class, 'rejectTrialRequest'])->name('trial.reject');

            // Subscription Session Scheduling
            Route::get('/subscription/{subscription}', [App\Http\Controllers\TeacherScheduleController::class, 'showSubscriptionScheduling'])->name('subscription.show');
            Route::post('/subscription/{subscription}', [App\Http\Controllers\TeacherScheduleController::class, 'setupSubscriptionSessions'])->name('subscription.setup');
        });

        // Meeting Link Management Routes
        Route::prefix('meetings')->name('teacher.meetings.')->group(function () {
            Route::get('/platforms', [App\Http\Controllers\MeetingLinkController::class, 'getMeetingPlatforms'])->name('platforms');
            Route::put('/session/{session}/link', [App\Http\Controllers\MeetingLinkController::class, 'updateSessionMeetingLink'])->name('session.update');
            Route::put('/trial/{trialRequest}/link', [App\Http\Controllers\MeetingLinkController::class, 'updateTrialMeetingLink'])->name('trial.update');
            Route::post('/session/{session}/generate', [App\Http\Controllers\MeetingLinkController::class, 'generateMeetingLink'])->name('session.generate');
        });

        // Academic Teacher Routes (Individual Lessons)
        Route::middleware('role:academic_teacher')->prefix('academic')->name('teacher.academic.')->group(function () {
            // Individual Academic Lessons
            Route::get('/lessons', [App\Http\Controllers\AcademicIndividualLessonController::class, 'index'])->name('lessons.index');
            Route::get('/lessons/{lesson}', [App\Http\Controllers\AcademicIndividualLessonController::class, 'show'])->name('lessons.show');
            Route::get('/lessons/{lesson}/progress', [App\Http\Controllers\AcademicIndividualLessonController::class, 'progressReport'])->name('lessons.progress');
            Route::put('/lessons/{lesson}/settings', [App\Http\Controllers\AcademicIndividualLessonController::class, 'updateSettings'])->name('lessons.update-settings');

            // Academic Sessions
            Route::get('/sessions', [App\Http\Controllers\AcademicSessionController::class, 'index'])->name('sessions.index');
            Route::get('/sessions/{session}', [App\Http\Controllers\AcademicSessionController::class, 'show'])->name('sessions.show');
            Route::put('/sessions/{session}/evaluation', [App\Http\Controllers\AcademicSessionController::class, 'updateEvaluation'])->name('sessions.evaluation');
            Route::put('/sessions/{session}/status', [App\Http\Controllers\AcademicSessionController::class, 'updateStatus'])->name('sessions.status');
            Route::put('/sessions/{session}/reschedule', [App\Http\Controllers\AcademicSessionController::class, 'reschedule'])->name('sessions.reschedule');
            Route::put('/sessions/{session}/cancel', [App\Http\Controllers\AcademicSessionController::class, 'cancel'])->name('sessions.cancel');
        });
    });

    // Supervisor routes (dashboard access)
    Route::middleware(['auth', 'role:supervisor'])->group(function () {
        Route::get('/supervisor', function () {
            return redirect('/supervisor/dashboard');
        })->name('supervisor.dashboard');
    });
});
