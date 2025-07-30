<?php

use Illuminate\Support\Facades\Route;
use App\Models\Academy;
use App\Http\Controllers\RecordedCourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentDashboardController;

/*
|--------------------------------------------------------------------------
| Main Domain Routes
|--------------------------------------------------------------------------
*/

// Main domain routes (itqan-platform.test or default academy)
Route::domain(config('app.domain'))->group(function () {
    Route::get('/', function () {
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
                
            $academies = Academy::where('status', 'active')->get();
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
    Route::get('/', function ($subdomain) {
        // Find academy by subdomain
        $academy = Academy::where('subdomain', $subdomain)->first();
        
        if (!$academy) {
            abort(404, 'Academy not found');
        }
        
        if (!$academy->is_active) {
            abort(503, 'Academy is currently unavailable');
        }
        
        if ($academy->maintenance_mode) {
            abort(503, 'Academy is currently under maintenance');
        }
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 8px;'>
            <h1 style='color: #2563eb;'>🎓 {$academy->name}</h1>
            <p><strong>Subdomain:</strong> {$subdomain}</p>
            <p><strong>Full Domain:</strong> {$academy->full_domain}</p>
            <p><strong>Status:</strong> {$academy->status}</p>
            <p><strong>Logo URL:</strong> " . ($academy->logo_url ?? 'No logo uploaded') . "</p>
            <hr>
            <p>🚀 <strong>Subdomain routing is working!</strong></p>
            <div style='margin-top: 20px;'>
                <a href='/courses' style='display: inline-block; margin: 5px; padding: 10px 20px; background: #16a34a; color: white; text-decoration: none; border-radius: 4px;'>Browse Courses</a>
                <a href='/dashboard' style='display: inline-block; margin: 5px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px;'>Student Dashboard</a>
                <a href='http://itqan-platform.test/admin' style='display: inline-block; margin: 5px; padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 4px;'>Admin Panel</a>
            </div>
        </div>
        ";
    })->name('academy.home');

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
    | Additional Academy Routes
    |--------------------------------------------------------------------------
    */
    
    // These can be extended for other features
    // Route::get('/teachers', [TeachersController::class, 'index'])->name('teachers.index');
    // Route::get('/subjects', [SubjectsController::class, 'index'])->name('subjects.index');
    // Route::get('/about', [AcademyController::class, 'about'])->name('academy.about');
    // Route::get('/contact', [AcademyController::class, 'contact'])->name('academy.contact');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Include authentication routes (these would typically be in a separate file)
// Auth::routes();

/*
|--------------------------------------------------------------------------
| Admin Routes (Global)
|--------------------------------------------------------------------------
*/

// Admin routes would typically be in a separate route file
// Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
//     // Admin routes
// });
