<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\StudentProfile;
use App\Models\User;

echo "=== Setup Test Interactive Course Enrollment ===\n\n";

// Get or create student profile
$user = User::where('email', 'abdelrahmanhamdy320@gmail.com')->first();

if (! $user) {
    echo "❌ User not found\n";
    exit(1);
}

echo "User: {$user->name} ({$user->email})\n";

// Create student profile if missing
$student = $user->studentProfile;
if (! $student) {
    echo "Creating student profile...\n";
    $student = StudentProfile::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'first_name' => explode(' ', $user->name)[0] ?? $user->name,
        'last_name' => explode(' ', $user->name, 2)[1] ?? '',
        'enrollment_date' => now(),
        'academic_status' => 'active',
    ]);
    echo "✅ Student profile created with ID: {$student->id}\n";
} else {
    echo "✅ Student profile exists with ID: {$student->id}\n";
}

// Get an interactive course
$course = InteractiveCourse::where('academy_id', $user->academy_id)->first();

if (! $course) {
    echo "❌ No interactive courses found in academy\n";
    exit(1);
}

echo "\nCourse: {$course->title}\n";
echo "Course ID: {$course->id}\n";
echo "Current Status: {$course->status}\n";

// Update course to be published/active if needed
if ($course->status !== 'active') {
    echo "Updating course status to 'active'...\n";
    $course->update([
        'status' => 'active',
        'is_published' => true,
    ]);
    echo "✅ Course is now active and published\n";
}

// Check if enrollment already exists
$existingEnrollment = InteractiveCourseEnrollment::where('course_id', $course->id)
    ->where('student_id', $student->id)
    ->first();

if ($existingEnrollment) {
    echo "\n⚠️  Enrollment already exists!\n";
    echo "Enrollment ID: {$existingEnrollment->id}\n";
    echo "Status: {$existingEnrollment->enrollment_status}\n";
    echo "Payment Status: {$existingEnrollment->payment_status}\n";

    if ($existingEnrollment->enrollment_status !== 'enrolled') {
        echo "\nUpdating enrollment status to 'enrolled'...\n";
        $existingEnrollment->update([
            'enrollment_status' => 'enrolled',
            'payment_status' => 'paid',
        ]);
        echo "✅ Enrollment updated\n";
    }
} else {
    echo "\nCreating new enrollment...\n";
    $enrollment = InteractiveCourseEnrollment::create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'academy_id' => $user->academy_id,
        'enrollment_status' => 'enrolled',
        'payment_status' => 'paid',
        'payment_amount' => $course->student_price ?? 0,
        'enrolled_at' => now(),
        'enrollment_date' => now(),
    ]);
    echo "✅ Enrollment created with ID: {$enrollment->id}\n";
}

echo "\n=== TEST NOW ===\n";
echo "1. Login as: {$user->email}\n";
echo '2. Visit: '.route('interactive-courses.show', [
    'subdomain' => $user->academy->subdomain,
    'course' => $course->id,
])."\n";
echo '3. You should be automatically redirected to: '.route('my.interactive-course.show', [
    'subdomain' => $user->academy->subdomain,
    'course' => $course->id,
])."\n";
echo "4. You should see: STUDENT VIEW (not public view)\n\n";

echo "✅ Setup complete!\n";
