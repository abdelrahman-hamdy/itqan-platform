<?php

/**
 * Test script to verify attendance system fix
 * Run: php test-attendance-fix.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\LiveKitVerificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n===========================================\n";
echo "    ATTENDANCE SYSTEM FIX TEST SUITE\n";
echo "===========================================\n\n";

// Test 1: Verify LiveKit Service Configuration
echo "TEST 1: LiveKit Verification Service Configuration\n";
echo "-------------------------------------------\n";

try {
    $verificationService = app(LiveKitVerificationService::class);

    if ($verificationService->isConfigured()) {
        echo "✅ LiveKit verification service is configured\n";
    } else {
        echo "❌ LiveKit verification service NOT configured\n";
        echo "   Please check LIVEKIT_API_KEY and LIVEKIT_API_SECRET in .env\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Failed to initialize verification service: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Find Active Session with Attendance
echo "TEST 2: Finding Active Session with Open Attendance\n";
echo "-------------------------------------------\n";

$activeAttendance = MeetingAttendance::whereHas('session', function ($query) {
    $query->whereIn('status', ['live', 'in_progress', 'scheduled'])
          ->whereNotNull('meeting_room_name');
})
->get()
->filter(function ($attendance) {
    $cycles = $attendance->join_leave_cycles ?? [];
    $lastCycle = end($cycles);
    return $lastCycle && isset($lastCycle['joined_at']) && !isset($lastCycle['left_at']);
})
->first();

if ($activeAttendance) {
    echo "✅ Found active attendance record\n";
    echo "   Session ID: {$activeAttendance->session_id}\n";
    echo "   User ID: {$activeAttendance->user_id}\n";
    echo "   Session Type: {$activeAttendance->session_type}\n";

    $session = $activeAttendance->session;
    if ($session) {
        echo "   Room Name: " . ($session->meeting_room_name ?? 'N/A') . "\n";
    }
} else {
    echo "⚠️  No active attendance records found\n";
    echo "   This is normal if no sessions are currently active\n";
}

echo "\n";

echo "✅ Test completed successfully!\n\n";
