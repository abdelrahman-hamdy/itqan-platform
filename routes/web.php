<?php

use Illuminate\Support\Facades\Route;
use App\Models\Academy;
use App\Http\Controllers\AcademyHomepageController;
use App\Http\Controllers\RecordedCourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentDashboardController;

// Include authentication routes
require __DIR__.'/auth.php';

// Test routes for academy styling verification
Route::get('/test-academy', function () {
    $academy = \App\Models\Academy::where('subdomain', 'itqan-academy')->first();
    if (!$academy) {
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
    if (!$academy) {
        return redirect('/')->with('error', 'Academy not found: ' . $subdomain);
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
        if (!$academy) {
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
        return redirect('http://itqan-academy.' . config('app.domain'));
    });
    
    Route::get('/old-home', function () {
        // Check if there's a default academy (itqan-academy)
        $defaultAcademy = Academy::where('subdomain', 'itqan-academy')->first();
        
        if ($defaultAcademy) {
            $output = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 8px;'>
                <h1 style='color: #2563eb;'>🎓 Itqan Platform</h1>
                <p><strong>Default Academy:</strong> {$defaultAcademy->name}</p>
                <p><strong>Domain:</strong> " . request()->getHost() . "</p>
                <hr>
                <h3>Available Academies:</h3>";
                
            $academies = Academy::where('is_active', true)->where('maintenance_mode', false)->get();
            foreach($academies as $academy) {
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
Route::domain('{subdomain}.' . config('app.domain'))->group(function () {
    
    // Academy Home Page
    Route::get('/', [AcademyHomepageController::class, 'show'])->name('academy.home');

    /*
    |--------------------------------------------------------------------------
    | Course Management Routes
    |--------------------------------------------------------------------------
    */
    
    // Course Listing & Discovery
    Route::get('/courses', [RecordedCourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [RecordedCourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [RecordedCourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [RecordedCourseController::class, 'show'])->name('courses.show');
    
    // Course Enrollment
    Route::post('/courses/{course}/enroll', [RecordedCourseController::class, 'enroll'])->name('courses.enroll');
    Route::get('/courses/{course}/checkout', [RecordedCourseController::class, 'checkout'])->name('courses.checkout');
    Route::get('/courses/{course}/learn', [RecordedCourseController::class, 'learn'])->name('courses.learn');

    /*
    |--------------------------------------------------------------------------
    | Lesson & Learning Routes
    |--------------------------------------------------------------------------
    */
    
    // Lesson Viewing & Progress
    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('/courses/{course}/lessons/{lesson}/progress', [LessonController::class, 'updateProgress'])->name('lessons.progress');
    Route::post('/courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'markCompleted'])->name('lessons.complete');
    
    // Lesson Interactions
    Route::post('/courses/{course}/lessons/{lesson}/bookmark', [LessonController::class, 'addBookmark'])->name('lessons.bookmark');
    Route::delete('/courses/{course}/lessons/{lesson}/bookmark', [LessonController::class, 'removeBookmark'])->name('lessons.unbookmark');
    Route::post('/courses/{course}/lessons/{lesson}/notes', [LessonController::class, 'addNote'])->name('lessons.notes.add');
    Route::get('/courses/{course}/lessons/{lesson}/notes', [LessonController::class, 'getNotes'])->name('lessons.notes.get');
    Route::post('/courses/{course}/lessons/{lesson}/rate', [LessonController::class, 'rate'])->name('lessons.rate');
    
    // Lesson Resources
    Route::get('/courses/{course}/lessons/{lesson}/transcript', [LessonController::class, 'getTranscript'])->name('lessons.transcript');
    Route::get('/courses/{course}/lessons/{lesson}/materials', [LessonController::class, 'downloadMaterials'])->name('lessons.materials');

    /*
    |--------------------------------------------------------------------------
    | Payment Routes
    |--------------------------------------------------------------------------
    */
    
    // Payment Processing
    Route::get('/courses/{course}/payment', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/courses/{course}/payment', [PaymentController::class, 'store'])->name('payments.store');
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
    Route::get('/my-courses', [StudentDashboardController::class, 'courses'])->name('student.courses');
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
    */
    
    // Student Profile Routes (Protected)
    Route::middleware(['auth', 'role:student'])->group(function () {
        Route::get('/profile', [App\Http\Controllers\StudentProfileController::class, 'index'])->name('student.profile');
        Route::get('/profile/edit', [App\Http\Controllers\StudentProfileController::class, 'edit'])->name('student.profile.edit');
        Route::put('/profile/update', [App\Http\Controllers\StudentProfileController::class, 'update'])->name('student.profile.update');
        Route::get('/settings', [App\Http\Controllers\StudentProfileController::class, 'settings'])->name('student.settings');
        Route::get('/subscriptions', [App\Http\Controllers\StudentProfileController::class, 'subscriptions'])->name('student.subscriptions');
        Route::get('/payments', [App\Http\Controllers\StudentProfileController::class, 'payments'])->name('student.payments');
        Route::get('/progress', [App\Http\Controllers\StudentProfileController::class, 'progress'])->name('student.progress');
        Route::get('/certificates', [App\Http\Controllers\StudentProfileController::class, 'certificates'])->name('student.certificates');
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
});
