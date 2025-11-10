<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\InteractiveCourseEnrollment;

echo "=== Remove Test Enrollment ===\n\n";

$enrollment = InteractiveCourseEnrollment::find(1);

if ($enrollment) {
    echo "Found enrollment:\n";
    echo "- ID: {$enrollment->id}\n";
    echo "- Student ID: {$enrollment->student_id}\n";
    echo "- Course ID: {$enrollment->course_id}\n";
    echo "- Status: {$enrollment->enrollment_status}\n\n";

    echo "Deleting...\n";
    $enrollment->delete();
    echo "✅ Test enrollment deleted\n";
} else {
    echo "No enrollment found with ID 1\n";
}
