<?php

/**
 * Direct Database Check for Parent Sessions
 * Run with: php check-parent-sessions-db.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=================================================\n";
echo "Parent Sessions Database Verification Tool\n";
echo "=================================================\n\n";

// Get parent email from command line or use default
$parentEmail = $argv[1] ?? null;

if (!$parentEmail) {
    echo "Usage: php check-parent-sessions-db.php <parent_email>\n";
    echo "Example: php check-parent-sessions-db.php parent@example.com\n\n";
    exit(1);
}

echo "Checking sessions for parent: {$parentEmail}\n\n";

// 1. Find parent user
$parentUser = DB::table('users')
    ->where('email', $parentEmail)
    ->where('role', 'parent')
    ->first();

if (!$parentUser) {
    echo "❌ Parent user not found with email: {$parentEmail}\n";
    echo "Please check the email address and try again.\n";
    exit(1);
}

echo "✅ Parent User Found\n";
echo "   ID: {$parentUser->id}\n";
echo "   Name: {$parentUser->name}\n";
echo "   Email: {$parentUser->email}\n\n";

// 2. Find parent profile
$parentProfile = DB::table('parent_profiles')
    ->where('user_id', $parentUser->id)
    ->first();

if (!$parentProfile) {
    echo "❌ Parent profile not found\n";
    exit(1);
}

echo "✅ Parent Profile Found\n";
echo "   Profile ID: {$parentProfile->id}\n\n";

// 3. Get children
$children = DB::table('parent_student_relationships as psr')
    ->join('student_profiles as sp', 'psr.student_id', '=', 'sp.id')
    ->join('users as u', 'sp.user_id', '=', 'u.id')
    ->where('psr.parent_id', $parentProfile->id)
    ->select('sp.id as profile_id', 'sp.user_id', 'u.name', 'u.email', 'sp.student_code')
    ->get();

if ($children->isEmpty()) {
    echo "❌ No children found for this parent\n";
    echo "Please add children to the parent account first.\n";
    exit(1);
}

echo "✅ Children Found: {$children->count()}\n";
foreach ($children as $child) {
    echo "   - {$child->name} (User ID: {$child->user_id}, Profile ID: {$child->profile_id}, Code: {$child->student_code})\n";
}
echo "\n";

// Get user IDs for session queries
$childrenUserIds = $children->pluck('user_id')->toArray();
$childrenProfileIds = $children->pluck('profile_id')->toArray();
echo "Using User IDs for queries: " . implode(', ', $childrenUserIds) . "\n";
echo "Using Profile IDs: " . implode(', ', $childrenProfileIds) . "\n\n";

// DIAGNOSTIC: Check what student_id values exist in sessions
echo "--- DIAGNOSTIC: Checking student_id in database ---\n";
$allQuranStudentIds = DB::table('quran_sessions')
    ->whereNotNull('scheduled_at')
    ->whereDate('scheduled_at', '>=', now()->toDateString())
    ->distinct()
    ->pluck('student_id')
    ->toArray();
echo "Quran Sessions have student_ids: " . implode(', ', $allQuranStudentIds) . "\n";

$allAcademicStudentIds = DB::table('academic_sessions')
    ->whereNotNull('scheduled_at')
    ->whereDate('scheduled_at', '>=', now()->toDateString())
    ->distinct()
    ->pluck('student_id')
    ->toArray();
echo "Academic Sessions have student_ids: " . implode(', ', $allAcademicStudentIds) . "\n\n";

// Check if there's a mismatch
$missingInQuran = array_diff($childrenUserIds, $allQuranStudentIds);
$missingInAcademic = array_diff($childrenUserIds, $allAcademicStudentIds);

if (!empty($missingInQuran) && !empty($allQuranStudentIds)) {
    echo "⚠️  WARNING: Children user IDs [" . implode(', ', $missingInQuran) . "] are NOT in quran_sessions.student_id\n";
}
if (!empty($missingInAcademic) && !empty($allAcademicStudentIds)) {
    echo "⚠️  WARNING: Children user IDs [" . implode(', ', $missingInAcademic) . "] are NOT in academic_sessions.student_id\n";
}

// Check the reverse - sessions that don't belong to these children
$extraInQuran = array_diff($allQuranStudentIds, $childrenUserIds);
$extraInAcademic = array_diff($allAcademicStudentIds, $childrenUserIds);

if (!empty($extraInQuran)) {
    echo "ℹ️  Quran sessions exist for OTHER students with IDs: " . implode(', ', $extraInQuran) . "\n";
}
if (!empty($extraInAcademic)) {
    echo "ℹ️  Academic sessions exist for OTHER students with IDs: " . implode(', ', $extraInAcademic) . "\n";
}
echo "\n";

// 4. Check Quran Sessions (WITHOUT status filter - matching calendar behavior)
echo "--- Quran Sessions (ALL statuses) ---\n";
$quranSessions = DB::table('quran_sessions as qs')
    ->leftJoin('users as student', 'qs.student_id', '=', 'student.id')
    ->leftJoin('users as teacher', 'qs.quran_teacher_id', '=', 'teacher.id')
    ->whereIn('qs.student_id', $childrenUserIds)
    ->whereNotNull('qs.scheduled_at')
    ->whereDate('qs.scheduled_at', '>=', now()->toDateString())
    ->select(
        'qs.id',
        'qs.student_id',
        'student.name as student_name',
        'qs.scheduled_at',
        'qs.status',
        'teacher.name as teacher_name'
    )
    ->orderBy('qs.scheduled_at')
    ->limit(10)
    ->get();

echo "ℹ️  Note: Calendar shows ALL sessions regardless of status\n";

if ($quranSessions->isEmpty()) {
    echo "❌ No upcoming Quran sessions found\n";
    echo "   Criteria: scheduled_at >= today, status NOT IN ('completed', 'cancelled')\n\n";
} else {
    echo "✅ Found {$quranSessions->count()} Quran session(s)\n";
    foreach ($quranSessions as $session) {
        echo "   📅 ID: {$session->id}\n";
        echo "      Student: {$session->student_name} (ID: {$session->student_id})\n";
        echo "      Teacher: {$session->teacher_name}\n";
        echo "      Scheduled: {$session->scheduled_at}\n";
        echo "      Status: {$session->status}\n";
        echo "\n";
    }
}

// 5. Check Academic Sessions
echo "--- Academic Sessions ---\n";
$academicSessions = DB::table('academic_sessions as as_tbl')
    ->leftJoin('users as student', 'as_tbl.student_id', '=', 'student.id')
    ->leftJoin('academic_teacher_profiles as atp', 'as_tbl.academic_teacher_id', '=', 'atp.id')
    ->leftJoin('users as teacher', 'atp.user_id', '=', 'teacher.id')
    ->whereIn('as_tbl.student_id', $childrenUserIds)
    ->whereNotNull('as_tbl.scheduled_at')
    ->whereDate('as_tbl.scheduled_at', '>=', now()->toDateString())
    ->select(
        'as_tbl.id',
        'as_tbl.student_id',
        'student.name as student_name',
        'as_tbl.scheduled_at',
        'as_tbl.status',
        'teacher.name as teacher_name'
    )
    ->orderBy('as_tbl.scheduled_at')
    ->limit(10)
    ->get();

if ($academicSessions->isEmpty()) {
    echo "❌ No upcoming Academic sessions found\n";
    echo "   Criteria: scheduled_at >= today, status NOT IN ('completed', 'cancelled')\n\n";
} else {
    echo "✅ Found {$academicSessions->count()} Academic session(s)\n";
    foreach ($academicSessions as $session) {
        echo "   📅 ID: {$session->id}\n";
        echo "      Student: {$session->student_name} (ID: {$session->student_id})\n";
        echo "      Teacher: {$session->teacher_name}\n";
        echo "      Scheduled: {$session->scheduled_at}\n";
        echo "      Status: {$session->status}\n";
        echo "\n";
    }
}

// 6. Summary
echo "=================================================\n";
echo "Summary\n";
echo "=================================================\n";
echo "Parent: {$parentUser->name} ({$parentEmail})\n";
echo "Children: {$children->count()}\n";
echo "Upcoming Quran Sessions: {$quranSessions->count()}\n";
echo "Upcoming Academic Sessions: {$academicSessions->count()}\n";
echo "Total Upcoming Sessions: " . ($quranSessions->count() + $academicSessions->count()) . "\n";
echo "=================================================\n\n";

if ($quranSessions->isEmpty() && $academicSessions->isEmpty()) {
    echo "ℹ️  No upcoming sessions found. Possible reasons:\n";
    echo "   1. No sessions scheduled for these children\n";
    echo "   2. All sessions are in the past\n";
    echo "   3. All sessions are marked as 'completed' or 'cancelled'\n";
    echo "   4. Sessions exist but scheduled_at is NULL\n\n";
    echo "To create test sessions, you can:\n";
    echo "   1. Use the Filament admin panel to create sessions\n";
    echo "   2. Schedule sessions with dates >= today\n";
    echo "   3. Ensure status is 'scheduled', 'ready', or 'live'\n";
} else {
    echo "✅ Sessions found! If they're not showing on the parent page:\n";
    echo "   1. Run: ./test-parent-sessions.sh\n";
    echo "   2. Navigate to parent profile page in browser\n";
    echo "   3. Check logs for '[Parent Upcoming Sessions]' entries\n";
    echo "   4. See PARENT_SESSIONS_DEBUG_GUIDE.md for details\n";
}

echo "\n";
