<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Main domain authentication routes (for Super-Admin)
// Note: Filament handles /admin/login automatically, so we don't need custom routes for admin panel

// Subdomain authentication routes
Route::domain('{subdomain}.' . config('app.domain'))->group(function () {
    
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

        Route::get('/recorded-courses', [App\Http\Controllers\StudentProfileController::class, 'recordedCourses'])->name('student.recorded-courses');
        Route::get('/interactive-courses', [App\Http\Controllers\StudentProfileController::class, 'interactiveCourses'])->name('student.interactive-courses');
        Route::get('/academic-teachers', [App\Http\Controllers\StudentProfileController::class, 'academicTeachers'])->name('student.academic-teachers');
        
        // Note: student.courses route is now handled in web.php
        
        Route::get('/my-assignments', function () {
            return view('student.assignments');
        })->name('student.assignments');
        
        // Quran sessions and subscription management
        Route::get('/debug-user', function() {
            $user = auth()->user();
            return response()->json([
                'authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'user_name' => $user->name ?? 'none',
                'academy_id' => $user->academy->id ?? 'none',
                'academy_subdomain' => $user->academy->subdomain ?? 'none',
                'is_student' => $user->isStudent() ?? false,
                'roles' => $user->roles ?? 'none'
            ]);
        })->name('debug.user');
        
        Route::get('/debug-subscription/{subscriptionId}', function($subscriptionId) {
            $user = auth()->user();
            $academy = $user->academy;
            
            $subscription = \App\Models\QuranSubscription::where('id', $subscriptionId)
                ->where('student_id', $user->id)
                ->where('academy_id', $academy->id)
                ->first();
                
            return response()->json([
                'subscriptionId' => $subscriptionId,
                'current_user' => $user->id,
                'current_academy' => $academy->id,
                'subscription_found' => $subscription ? 'yes' : 'no',
                'subscription_details' => $subscription ? [
                    'id' => $subscription->id,
                    'student_id' => $subscription->student_id,
                    'academy_id' => $subscription->academy_id,
                    'teacher' => $subscription->quranTeacher?->full_name
                ] : null
            ]);
        })->name('debug.subscription');
        
        Route::get('/quran/sessions/{subscriptionId}', [App\Http\Controllers\StudentProfileController::class, 'quranSessions'])->name('student.quran.sessions');
        
        // Course detail pages
        Route::get('/courses/{course}', [App\Http\Controllers\StudentProfileController::class, 'courseShow'])->name('student.courses.show');
        
        // Certificate downloads
        Route::get('/certificates/{enrollment}/download', [App\Http\Controllers\StudentProfileController::class, 'downloadCertificate'])->name('student.certificates.download');
        
        // Quran circle management
        Route::get('/circles/{circleId}', [App\Http\Controllers\StudentProfileController::class, 'showCircle'])->name('student.circles.show');
        Route::post('/circles/{circleId}/enroll', [App\Http\Controllers\StudentProfileController::class, 'enrollInCircle'])->name('student.circles.enroll');
        Route::post('/circles/{circleId}/leave', [App\Http\Controllers\StudentProfileController::class, 'leaveCircle'])->name('student.circles.leave');
        
        // Individual circles management
        Route::get('/individual-circles/{circleId}', [App\Http\Controllers\StudentProfileController::class, 'showIndividualCircle'])->name('student.individual-circles.show');
        
        // Session routes for students
        Route::get('/sessions/{sessionId}', [App\Http\Controllers\QuranSessionController::class, 'showForStudent'])->name('student.sessions.show');
        Route::put('/sessions/{sessionId}/feedback', [App\Http\Controllers\QuranSessionController::class, 'addFeedback'])->name('student.sessions.feedback');
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
    Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->group(function () {
        // Main teacher route redirects to profile
        Route::get('/', function () {
            $subdomain = request()->route('subdomain') ?? Auth::user()->academy->subdomain ?? 'itqan-academy';
            return redirect()->route('teacher.profile', ['subdomain' => $subdomain]);
        })->name('teacher.dashboard');
        
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
    });

    // Supervisor routes (dashboard access)
    Route::middleware(['auth', 'role:supervisor'])->group(function () {
        Route::get('/supervisor', function () {
            return redirect('/supervisor/dashboard');
        })->name('supervisor.dashboard');
    });
}); 