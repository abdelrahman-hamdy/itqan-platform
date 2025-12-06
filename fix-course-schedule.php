<?php

/**
 * Fix the schedule data format for the test interactive course
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InteractiveCourse;

try {
    echo "🔧 Fixing course schedule data format...\n\n";

    $course = InteractiveCourse::find(3);

    if (!$course) {
        throw new Exception("Course not found with ID: 3");
    }

    echo "📚 Found Course: {$course->title}\n";
    echo "   Current Schedule: " . json_encode($course->schedule, JSON_UNESCAPED_UNICODE) . "\n\n";

    // Update schedule to correct format (associative array with Arabic day names)
    $course->schedule = [
        'الأحد' => '10:00 - 11:00',
        'الأربعاء' => '10:00 - 11:00',
    ];

    $course->save();

    echo "✅ Schedule updated successfully!\n";
    echo "   New Schedule: " . json_encode($course->schedule, JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "✨ Done! The course schedule is now in the correct format.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
