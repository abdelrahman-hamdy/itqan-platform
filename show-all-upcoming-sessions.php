<?php

/**
 * Show ALL Upcoming Sessions in Database
 * Run with: php show-all-upcoming-sessions.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=================================================\n";
echo "ALL Upcoming Sessions in Database\n";
echo "=================================================\n\n";

// 1. Show ALL Quran Sessions
echo "--- ALL QURAN SESSIONS (today and future) ---\n";
$quranSessions = DB::table('quran_sessions')
    ->leftJoin('users as student', 'quran_sessions.student_id', '=', 'student.id')
    ->whereNotNull('quran_sessions.scheduled_at')
    ->whereDate('quran_sessions.scheduled_at', '>=', now()->toDateString())
    ->select(
        'quran_sessions.id',
        'quran_sessions.student_id',
        'student.name as student_name',
        'student.email as student_email',
        'quran_sessions.scheduled_at',
        'quran_sessions.status'
    )
    ->orderBy('quran_sessions.scheduled_at')
    ->limit(20)
    ->get();

if ($quranSessions->isEmpty()) {
    echo "❌ No Quran sessions found\n\n";
} else {
    echo "✅ Found {$quranSessions->count()} Quran session(s)\n\n";
    foreach ($quranSessions as $session) {
        echo "ID: {$session->id} | Student ID: {$session->student_id} | {$session->student_name} ({$session->student_email}) | {$session->scheduled_at} | Status: {$session->status}\n";
    }
    echo "\n";
}

// 2. Show ALL Academic Sessions
echo "--- ALL ACADEMIC SESSIONS (today and future) ---\n";
$academicSessions = DB::table('academic_sessions')
    ->leftJoin('users as student', 'academic_sessions.student_id', '=', 'student.id')
    ->whereNotNull('academic_sessions.scheduled_at')
    ->whereDate('academic_sessions.scheduled_at', '>=', now()->toDateString())
    ->select(
        'academic_sessions.id',
        'academic_sessions.student_id',
        'student.name as student_name',
        'student.email as student_email',
        'academic_sessions.scheduled_at',
        'academic_sessions.status'
    )
    ->orderBy('academic_sessions.scheduled_at')
    ->limit(20)
    ->get();

if ($academicSessions->isEmpty()) {
    echo "❌ No Academic sessions found\n\n";
} else {
    echo "✅ Found {$academicSessions->count()} Academic session(s)\n\n";
    foreach ($academicSessions as $session) {
        echo "ID: {$session->id} | Student ID: {$session->student_id} | {$session->student_name} ({$session->student_email}) | {$session->scheduled_at} | Status: {$session->status}\n";
    }
    echo "\n";
}

// 3. Show ALL Interactive Course Sessions
echo "--- ALL INTERACTIVE COURSE SESSIONS (today and future) ---\n";
$courseSessions = DB::table('interactive_course_sessions')
    ->whereNotNull('interactive_course_sessions.scheduled_at')
    ->whereDate('interactive_course_sessions.scheduled_at', '>=', now()->toDateString())
    ->select(
        'interactive_course_sessions.id',
        'interactive_course_sessions.interactive_course_id',
        'interactive_course_sessions.scheduled_at',
        'interactive_course_sessions.status'
    )
    ->orderBy('interactive_course_sessions.scheduled_at')
    ->limit(20)
    ->get();

if ($courseSessions->isEmpty()) {
    echo "❌ No Interactive Course sessions found\n\n";
} else {
    echo "✅ Found {$courseSessions->count()} Interactive Course session(s)\n\n";
    foreach ($courseSessions as $session) {
        echo "ID: {$session->id} | Course ID: {$session->interactive_course_id} | {$session->scheduled_at} | Status: {$session->status}\n";
    }
    echo "\n";
}

echo "=================================================\n";
echo "Summary\n";
echo "=================================================\n";
echo "Total Quran Sessions: {$quranSessions->count()}\n";
echo "Total Academic Sessions: {$academicSessions->count()}\n";
echo "Total Interactive Course Sessions: {$courseSessions->count()}\n";
echo "GRAND TOTAL: " . ($quranSessions->count() + $academicSessions->count() + $courseSessions->count()) . "\n";
echo "=================================================\n\n";

if ($quranSessions->isEmpty() && $academicSessions->isEmpty() && $courseSessions->isEmpty()) {
    echo "⚠️  NO SESSIONS FOUND IN DATABASE!\n";
    echo "Please create some test sessions first.\n";
} else {
    echo "✅ Sessions exist in database.\n";
    echo "   Now run: php check-parent-sessions-db.php parent@example.com\n";
    echo "   to check if parent's children match these student IDs.\n";
}

echo "\n";
