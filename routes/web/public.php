<?php

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| Platform landing page, academy homepage, static pages, and public
| browsing of teachers, courses, and circles.
*/

use App\Http\Controllers\AcademyHomepageController;
use App\Http\Controllers\PublicAcademicPackageController;
use App\Http\Controllers\RecordedCourseController;
use App\Http\Controllers\UnifiedAcademicTeacherController;
use App\Http\Controllers\UnifiedInteractiveCourseController;
use App\Http\Controllers\UnifiedQuranCircleController;
use App\Http\Controllers\UnifiedQuranTeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Main Domain Routes (Platform Landing Page)
|--------------------------------------------------------------------------
*/

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
        $defaultAcademy = \App\Models\Academy::where('subdomain', 'itqan-academy')->first();

        if ($defaultAcademy) {
            $output = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; border: 1px solid #ddd; border-radius: 8px;'>
                <h1 style='color: #2563eb;'>🎓 Itqan Platform</h1>
                <p><strong>Default Academy:</strong> {$defaultAcademy->name}</p>
                <p><strong>Domain:</strong> ".request()->getHost().'</p>
                <hr>
                <h3>Available Academies:</h3>';

            $academies = \App\Models\Academy::where('is_active', true)->where('maintenance_mode', false)->get();
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
| Subdomain Public Routes
|--------------------------------------------------------------------------
*/

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
    | Unified Quran Teacher Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Quran Teachers Listing (works for both public and authenticated)
    Route::get('/quran-teachers', [UnifiedQuranTeacherController::class, 'index'])->name('quran-teachers.index');

    // UNIFIED Individual Teacher Profile Pages
    Route::get('/quran-teachers/{teacherId}', [UnifiedQuranTeacherController::class, 'show'])->name('quran-teachers.show');

    /*
    |--------------------------------------------------------------------------
    | Unified Academic Teacher Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Academic Teachers Listing (works for both public and authenticated)
    Route::get('/academic-teachers', [UnifiedAcademicTeacherController::class, 'index'])->name('academic-teachers.index');

    // UNIFIED Individual Academic Teacher Profile Pages
    Route::get('/academic-teachers/{teacherId}', [UnifiedAcademicTeacherController::class, 'show'])->name('academic-teachers.show');

    /*
    |--------------------------------------------------------------------------
    | Public Academic Package Routes
    |--------------------------------------------------------------------------
    */

    // Public Academic Packages Listing
    Route::get('/academic-packages', [PublicAcademicPackageController::class, 'index'])->name('public.academic-packages.index');

    // Individual Teacher Profile for Academic Packages
    Route::get('/academic-packages/teachers/{teacher}', [PublicAcademicPackageController::class, 'showTeacher'])->name('public.academic-packages.teacher');

    // API: Get teachers for a specific package
    Route::get('/api/academic-packages/{packageId}/teachers', [PublicAcademicPackageController::class, 'getPackageTeachers'])->name('api.academic-packages.teachers');

    /*
    |--------------------------------------------------------------------------
    | Unified Quran Circle Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Quran Circles Listing (works for both public and authenticated)
    Route::get('/quran-circles', [UnifiedQuranCircleController::class, 'index'])->name('quran-circles.index');

    // UNIFIED Individual Circle Details Pages
    Route::get('/quran-circles/{circleId}', [UnifiedQuranCircleController::class, 'show'])->name('quran-circles.show');

    /*
    |--------------------------------------------------------------------------
    | Unified Interactive Courses Routes (Public + Authenticated)
    |--------------------------------------------------------------------------
    */

    // UNIFIED Interactive Courses Listing (works for both public and authenticated)
    Route::get('/interactive-courses', [UnifiedInteractiveCourseController::class, 'index'])->name('interactive-courses.index');

    // UNIFIED Individual Interactive Course Details
    Route::get('/interactive-courses/{courseId}', [UnifiedInteractiveCourseController::class, 'show'])->name('interactive-courses.show');

    /*
    |--------------------------------------------------------------------------
    | Public Recorded Courses Routes (Unified)
    |--------------------------------------------------------------------------
    */

    // Unified Recorded Courses Listing (works for both public and authenticated users)
    Route::get('/courses', [RecordedCourseController::class, 'index'])->name('courses.index');

    // Course Detail - Public Access (shows different content based on auth status)
    Route::get('/courses/{id}', [RecordedCourseController::class, 'show'])
        ->name('courses.show')
        ->where('id', '[0-9]+');
});
