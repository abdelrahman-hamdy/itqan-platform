<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\Academy;

echo "=== Interactive Course Access Debug ===\n\n";

// Get a student user (you can change this to your test user email)
echo "Enter student email (or press Enter for first student): ";
$handle = fopen("php://stdin", "r");
$email = trim(fgets($handle));
fclose($handle);

if (empty($email)) {
    $student = User::where('user_type', 'student')->first();
    if (!$student) {
        echo "❌ No students found in database\n";
        exit(1);
    }
    echo "Using first student found: {$student->email}\n\n";
} else {
    $student = User::where('email', $email)->first();
    if (!$student) {
        echo "❌ Student not found with email: {$email}\n";
        exit(1);
    }
}

echo "Student Details:\n";
echo "- ID: {$student->id}\n";
echo "- Name: {$student->name}\n";
echo "- Email: {$student->email}\n";
echo "- User Type: {$student->user_type}\n";
echo "- Academy: " . ($student->academy->name ?? 'None') . "\n";
echo "- Student Profile ID: " . ($student->studentProfile->id ?? 'None') . "\n\n";

$studentId = $student->studentProfile->id ?? $student->id;

// Get all interactive courses for this academy
$courses = InteractiveCourse::where('academy_id', $student->academy_id)
    ->where('is_published', true)
    ->get();

echo "Published Interactive Courses in Academy:\n";
if ($courses->isEmpty()) {
    echo "❌ No published courses found\n";
    exit(1);
}

foreach ($courses as $course) {
    echo "\nCourse ID {$course->id}: {$course->title}\n";
    echo "- Status: {$course->status}\n";
    echo "- Max Students: {$course->max_students}\n";

    // Check enrollment for this student
    $enrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
        ->where('student_id', $studentId)
        ->first();

    if ($enrollment) {
        echo "- ✅ ENROLLED\n";
        echo "  - Enrollment ID: {$enrollment->id}\n";
        echo "  - Status: {$enrollment->enrollment_status}\n";
        echo "  - Payment Status: {$enrollment->payment_status}\n";
        echo "  - Enrolled At: {$enrollment->enrolled_at}\n";

        // Check what middleware would do
        echo "\n  🔍 Middleware Decision:\n";
        if (in_array($enrollment->enrollment_status, ['enrolled', 'completed'])) {
            echo "  → Should redirect to: /my-interactive-courses/{$course->id} (STUDENT VIEW)\n";
        } elseif ($enrollment->enrollment_status === 'pending') {
            echo "  → Should redirect to: /interactive-courses/{$course->id}/enroll (PAYMENT)\n";
        } else {
            echo "  → Should allow: /interactive-courses/{$course->id} (PUBLIC VIEW)\n";
        }
    } else {
        echo "- ❌ NOT ENROLLED\n";
        echo "  → Should allow: /interactive-courses/{$course->id} (PUBLIC VIEW to enroll)\n";
    }
}

echo "\n\n=== Route Testing ===\n";

// Test route generation
$academy = $student->academy;
$firstCourse = $courses->first();

echo "\nPublic Route: " . route('interactive-courses.show', [
    'subdomain' => $academy->subdomain,
    'course' => $firstCourse->id
]) . "\n";

echo "Student Route: " . route('my.interactive-course.show', [
    'subdomain' => $academy->subdomain,
    'course' => $firstCourse->id
]) . "\n";

echo "\n=== Middleware Check ===\n";

// Check if middleware is registered
$middleware = app('router')->getMiddleware();
if (isset($middleware['redirect.authenticated.public'])) {
    echo "✅ Middleware 'redirect.authenticated.public' is registered\n";
} else {
    echo "❌ Middleware 'redirect.authenticated.public' NOT registered!\n";
    echo "   Check app/Http/Kernel.php\n";
}

echo "\n=== Route Middleware Check ===\n";

// Find the route
$routes = app('router')->getRoutes();
$publicRoute = $routes->getByName('interactive-courses.show');

if ($publicRoute) {
    echo "✅ Route 'interactive-courses.show' exists\n";
    echo "   Middleware: " . json_encode($publicRoute->gatherMiddleware()) . "\n";
} else {
    echo "❌ Route 'interactive-courses.show' NOT found\n";
}

echo "\n=== SUMMARY ===\n";

$enrollment = InteractiveCourseEnrollment::where('course_id', $firstCourse->id)
    ->where('student_id', $studentId)
    ->first();

if ($enrollment && in_array($enrollment->enrollment_status, ['enrolled', 'completed'])) {
    echo "✅ Student IS enrolled with active status\n";
    echo "✅ Should see STUDENT VIEW at: /my-interactive-courses/{$firstCourse->id}\n";
    echo "\n⚠️  If seeing PUBLIC VIEW, the issue is:\n";
    echo "   1. Middleware not running (check route definition)\n";
    echo "   2. Cache issue (run: php artisan route:clear && php artisan config:clear)\n";
    echo "   3. Browser cache (try incognito mode)\n";
} else {
    echo "❌ Student is NOT enrolled or enrollment status is: " . ($enrollment->enrollment_status ?? 'none') . "\n";
    echo "✅ Should see PUBLIC VIEW (correct behavior)\n";
    echo "\n💡 To fix: Enroll student or change enrollment status to 'enrolled'\n";
}

echo "\n";
