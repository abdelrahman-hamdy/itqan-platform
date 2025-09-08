<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\User;

// Test the interactive course route issue
echo "=== TESTING INTERACTIVE COURSE ROUTE ===\n\n";

// 1. Check if course ID 3 exists
$course = InteractiveCourse::find(3);
if ($course) {
    echo "✅ Course ID 3 exists\n";
    echo "   - Title: {$course->title}\n";
    echo "   - Academy ID: {$course->academy_id}\n";
    echo "   - Assigned Teacher ID: {$course->assigned_teacher_id}\n";
    echo "   - Status: {$course->status}\n\n";
} else {
    echo "❌ Course ID 3 does NOT exist!\n\n";
}

// 2. Check which academy has subdomain 'itqan-academy'
$academy = Academy::where('subdomain', 'itqan-academy')->first();
if ($academy) {
    echo "✅ Academy with subdomain 'itqan-academy' exists\n";
    echo "   - Academy ID: {$academy->id}\n";
    echo "   - Name: {$academy->name}\n\n";
} else {
    echo "❌ No academy with subdomain 'itqan-academy'!\n\n";
}

// 3. Check if course belongs to the academy
if ($course && $academy) {
    if ($course->academy_id == $academy->id) {
        echo "✅ Course belongs to the academy\n\n";
    } else {
        echo "❌ Course does NOT belong to the academy!\n";
        echo "   Course academy_id: {$course->academy_id}\n";
        echo "   Expected academy_id: {$academy->id}\n\n";
    }
}

// 4. Check a sample student user
$student = User::where('user_type', 'student')->where('academy_id', $academy->id ?? 1)->first();
if ($student) {
    echo "✅ Found student user\n";
    echo "   - ID: {$student->id}\n";
    echo "   - Name: {$student->name}\n";
    echo "   - Academy ID: {$student->academy_id}\n\n";
} else {
    echo "❌ No student found for this academy\n\n";
}

// 5. Check an academic teacher user
$teacher = User::where('user_type', 'academic_teacher')->where('academy_id', $academy->id ?? 1)->first();
if ($teacher) {
    echo "✅ Found academic teacher user\n";
    echo "   - ID: {$teacher->id}\n";
    echo "   - Name: {$teacher->name}\n";
    echo "   - Academy ID: {$teacher->academy_id}\n";

    // Check if teacher has profile
    $profile = $teacher->academicTeacherProfile;
    if ($profile) {
        echo "   - Profile ID: {$profile->id}\n";

        // Check if this teacher is assigned to the course
        if ($course && $course->assigned_teacher_id == $profile->id) {
            echo "   ✅ This teacher IS assigned to the course\n\n";
        } else {
            echo "   ❌ This teacher is NOT assigned to the course\n";
            if ($course) {
                echo "   Course assigned_teacher_id: {$course->assigned_teacher_id}\n";
                echo "   Teacher profile ID: {$profile->id}\n\n";
            }
        }
    } else {
        echo "   ❌ Teacher has no profile!\n\n";
    }
} else {
    echo "❌ No academic teacher found for this academy\n\n";
}

// 6. List all interactive courses in the system
echo "=== ALL INTERACTIVE COURSES ===\n";
$allCourses = InteractiveCourse::all();
foreach ($allCourses as $c) {
    echo "ID: {$c->id} | Title: {$c->title} | Academy: {$c->academy_id} | Teacher: {$c->assigned_teacher_id}\n";
}

// 7. Test route generation
echo "\n=== ROUTE GENERATION TEST ===\n";
try {
    $url = route('interactive-courses.show', ['subdomain' => 'itqan-academy', 'course' => 3]);
    echo "✅ Route generated successfully: {$url}\n";
} catch (\Exception $e) {
    echo '❌ Route generation failed: '.$e->getMessage()."\n";
}
