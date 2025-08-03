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
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/profile', [App\Http\Controllers\StudentProfileController::class, 'index'])->name('student.profile');
        Route::get('/profile/edit', [App\Http\Controllers\StudentProfileController::class, 'edit'])->name('student.profile.edit');
        Route::put('/profile/update', [App\Http\Controllers\StudentProfileController::class, 'update'])->name('student.profile.update');
        Route::get('/settings', [App\Http\Controllers\StudentProfileController::class, 'settings'])->name('student.settings');
        Route::get('/subscriptions', [App\Http\Controllers\StudentProfileController::class, 'subscriptions'])->name('student.subscriptions');
        Route::get('/payments', [App\Http\Controllers\StudentProfileController::class, 'payments'])->name('student.payments');
        Route::get('/progress', [App\Http\Controllers\StudentProfileController::class, 'progress'])->name('student.progress');
        Route::get('/certificates', [App\Http\Controllers\StudentProfileController::class, 'certificates'])->name('student.certificates');
        
        // Legacy routes for backward compatibility
        Route::get('/my-courses', function () {
            return view('student.courses');
        })->name('student.courses');
        
        Route::get('/my-assignments', function () {
            return view('student.assignments');
        })->name('student.assignments');
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
        Route::get('/settings', [App\Http\Controllers\TeacherProfileController::class, 'settings'])->name('teacher.settings');
    });

    // Supervisor routes (dashboard access)
    Route::middleware(['auth', 'role:supervisor'])->group(function () {
        Route::get('/supervisor', function () {
            return redirect('/supervisor/dashboard');
        })->name('supervisor.dashboard');
    });
}); 