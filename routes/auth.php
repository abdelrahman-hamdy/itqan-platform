<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ParentRegistrationController;
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
    | Password Reset Routes
    |--------------------------------------------------------------------------
    */

    // Forgot password - show form and send reset link
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');

    // Reset password - show form and process reset
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');

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
    | Parent Registration Routes
    |--------------------------------------------------------------------------
    */

    // Parent registration with student code verification (public access)
    Route::get('/parent/register', [ParentRegistrationController::class, 'showRegistrationForm'])->name('parent.register');
    Route::post('/parent/register', [ParentRegistrationController::class, 'register'])->name('parent.register.post');
    Route::post('/parent/verify-students', [ParentRegistrationController::class, 'verifyStudentCodes'])->name('parent.verify.students');

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
        // Note: /certificates route moved to web.php CertificateController for unified handling

        // Interactive courses routes moved to web.php for proper ordering
        // Academic teachers route moved to web.php for subdomain compatibility

        // Note: student.courses route is now handled in web.php

        Route::get('/my-assignments', function () {
            return view('student.assignments');
        })->name('student.assignments');

        // Quran sessions management moved to individual circles

        // Course detail pages - REMOVED: Moved to main web.php with ID-based routing
        // Route::get('/courses/{course}', [App\Http\Controllers\StudentProfileController::class, 'courseShow'])->name('student.courses.show');

        // Certificate downloads - MOVED to web.php CertificateController
        // Route::get('/certificates/{enrollment}/download', [App\Http\Controllers\StudentProfileController::class, 'downloadCertificate'])->name('student.certificates.download');

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

    // Supervisor routes (dashboard access)
    Route::middleware(['auth', 'role:supervisor'])->prefix('supervisor')->group(function () {
        Route::get('/', function () {
            return redirect('/supervisor-panel');
        })->name('supervisor.dashboard');
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

            // Note: Academic session routes consolidated in web.php under teacher.academic-sessions.*
            // This provides better URL consistency: /teacher/academic-sessions/{session}
        });
    });

});
