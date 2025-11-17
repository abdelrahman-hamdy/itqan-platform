<?php

/**
 * Quick diagnostic script for attendance issues
 * Run: php diagnose-attendance.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;

echo "\n===========================================\n";
echo "    ATTENDANCE DIAGNOSTIC TOOL\n";
echo "===========================================\n\n";

// Get recent attendance records
echo "📊 Recent Attendance Records:\n";
echo "-------------------------------------------\n";

$recentAttendance = MeetingAttendance::orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentAttendance->count() === 0) {
    echo "❌ NO attendance records found!\n";
    echo "   This means webhooks are not creating records.\n";
} else {
    echo "✅ Found {$recentAttendance->count()} recent records\n\n";

    foreach ($recentAttendance as $att) {
        $cycles = $att->join_leave_cycles ?? [];
        $hasOpenCycle = false;

        if (!empty($cycles)) {
            $lastCycle = end($cycles);
            $hasOpenCycle = isset($lastCycle['joined_at']) && !isset($lastCycle['left_at']);
        }

        $status = $hasOpenCycle ? '🟢 OPEN' : '🔴 CLOSED';

        echo "  Session #{$att->session_id} | User #{$att->user_id}\n";
        echo "    Status: {$status}\n";
        echo "    Total Duration: {$att->total_duration_minutes} min\n";
        echo "    Cycles: " . count($cycles) . "\n";
        echo "    Created: {$att->created_at}\n";
        echo "\n";
    }
}

// Check active sessions
echo "\n📋 Active Sessions with Room Names:\n";
echo "-------------------------------------------\n";

$quranSessions = QuranSession::whereIn('status', ['live', 'in_progress', 'scheduled'])
    ->whereNotNull('meeting_room_name')
    ->get();

$academicSessions = AcademicSession::whereIn('status', ['live', 'in_progress', 'scheduled'])
    ->whereNotNull('meeting_room_name')
    ->get();

$totalActive = $quranSessions->count() + $academicSessions->count();

if ($totalActive === 0) {
    echo "⚠️  No active sessions found\n";
} else {
    echo "✅ Found {$totalActive} active sessions\n\n";

    foreach ($quranSessions as $session) {
        echo "  Quran Session #{$session->id}: {$session->title}\n";
        echo "    Room: {$session->meeting_room_name}\n";
        echo "    Status: {$session->status}\n\n";
    }

    foreach ($academicSessions as $session) {
        echo "  Academic Session #{$session->id}: {$session->title}\n";
        echo "    Room: {$session->meeting_room_name}\n";
        echo "    Status: {$session->status}\n\n";
    }
}

// Check recent logs
echo "\n📝 Recent Webhook Logs (last 50 lines):\n";
echo "-------------------------------------------\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $relevantLines = array_filter($lines, function($line) {
        return stripos($line, 'livekit') !== false ||
               stripos($line, 'webhook') !== false ||
               stripos($line, 'participant joined') !== false;
    });

    $lastLines = array_slice($relevantLines, -10);

    if (empty($lastLines)) {
        echo "❌ No webhook-related logs found\n";
        echo "   Webhooks may not be reaching the server!\n";
    } else {
        echo "✅ Found webhook activity:\n\n";
        foreach ($lastLines as $line) {
            echo "  " . trim($line) . "\n";
        }
    }
} else {
    echo "⚠️  Log file not found\n";
}

// Recommendations
echo "\n\n===========================================\n";
echo "    RECOMMENDATIONS\n";
echo "===========================================\n\n";

if ($recentAttendance->count() === 0) {
    echo "🔴 CRITICAL: No attendance records!\n";
    echo "   1. Check if LiveKit webhooks are configured\n";
    echo "   2. Verify webhook URL in LiveKit dashboard\n";
    echo "   3. Check firewall/network allows webhooks\n";
    echo "   4. Test webhook endpoint manually\n";
} else if ($totalActive === 0) {
    echo "ℹ️  No active sessions - attendance system idle\n";
    echo "   Start a session to test attendance\n";
} else {
    echo "✅ System appears to be working\n";
    echo "   If you're still seeing issues:\n";
    echo "   1. Check browser console for errors\n";
    echo "   2. Verify you're actually joining LiveKit room\n";
    echo "   3. Check network tab for API calls\n";
}

echo "\n✅ Diagnostic complete!\n\n";
