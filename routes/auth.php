<?php

use Illuminate\Support\Facades\Route;
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
        Route::get('/profile', function () {
            return view('student.profile');
        })->name('student.profile');
        
        Route::get('/my-courses', function () {
            return view('student.courses');
        })->name('student.courses');
        
        Route::get('/my-progress', function () {
            return view('student.progress');
        })->name('student.progress');
        
        Route::get('/my-assignments', function () {
            return view('student.assignments');
        })->name('student.assignments');
        
        Route::get('/my-payments', function () {
            return view('student.payments');
        })->name('student.payments');
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

    // Staff routes (dashboard access)
    Route::middleware(['auth', 'role:staff'])->group(function () {
        // Academy Admin routes
        Route::middleware(['role:academy_admin'])->group(function () {
            Route::get('/panel', function () {
                return redirect('/panel/dashboard');
            })->name('academy.admin.dashboard');
        });

        // Teacher routes
        Route::middleware(['role:teacher'])->group(function () {
            Route::get('/teacher', function () {
                return redirect('/teacher/dashboard');
            })->name('teacher.dashboard');
        });

        // Supervisor routes
        Route::middleware(['role:supervisor'])->group(function () {
            Route::get('/supervisor', function () {
                return redirect('/supervisor/dashboard');
            })->name('supervisor.dashboard');
        });
    });
}); 