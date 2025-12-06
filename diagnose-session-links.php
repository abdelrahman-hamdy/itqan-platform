<?php

/**
 * Diagnose How Sessions Are Linked to Students
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=================================================\n";
echo "Session Linkage Diagnosis\n";
echo "=================================================\n\n";

// Check a few sample sessions to see how they're linked
$sampleSessions = DB::table('quran_sessions')
    ->whereIn('id', [15, 4, 5, 6, 7])
    ->select([
        'id',
        'student_id',
        'circle_id',
        'individual_circle_id',
        'quran_subscription_id',
        'session_type',
        'scheduled_at'
    ])
    ->get();

echo "Sample Quran Sessions (IDs: 15, 4, 5, 6, 7):\n";
echo "=" . str_repeat("=", 100) . "\n";
foreach ($sampleSessions as $session) {
    echo "Session ID: {$session->id}\n";
    echo "  student_id: " . ($session->student_id ?: 'NULL') . "\n";
    echo "  circle_id: " . ($session->circle_id ?: 'NULL') . "\n";
    echo "  individual_circle_id: " . ($session->individual_circle_id ?: 'NULL') . "\n";
    echo "  quran_subscription_id: " . ($session->quran_subscription_id ?: 'NULL') . "\n";
    echo "  session_type: {$session->session_type}\n";
    echo "  scheduled_at: {$session->scheduled_at}\n";

    // If linked to circle, get circle students
    if ($session->circle_id) {
        $circleStudents = DB::table('quran_circle_student')
            ->where('quran_circle_id', $session->circle_id)
            ->pluck('student_id')
            ->toArray();
        echo "  → Circle students (User IDs): " . implode(', ', $circleStudents) . "\n";
    }

    // If linked to individual circle, get student from circle
    if ($session->individual_circle_id) {
        $individualCircle = DB::table('quran_individual_circles')
            ->where('id', $session->individual_circle_id)
            ->first();
        if ($individualCircle) {
            echo "  → Individual Circle student_id: " . ($individualCircle->student_id ?: 'NULL') . "\n";
        }
    }

    // If linked to subscription, get student from subscription
    if ($session->quran_subscription_id) {
        $subscription = DB::table('quran_subscriptions')
            ->where('id', $session->quran_subscription_id)
            ->first();
        if ($subscription) {
            echo "  → Subscription student_id: " . ($subscription->student_id ?: 'NULL') . "\n";
        }
    }

    echo "\n";
}

echo "\n=================================================\n";
echo "Conclusion\n";
echo "=================================================\n";
echo "Sessions are linked to students via:\n";
echo "1. Direct student_id (seems to be NULL)\n";
echo "2. circle_id → quran_circle_student pivot table\n";
echo "3. individual_circle_id → quran_individual_circles.student_id\n";
echo "4. quran_subscription_id → quran_subscriptions.student_id\n";
echo "\nWe need to update the query to check ALL these relationships!\n";
