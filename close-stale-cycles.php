<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MeetingAttendance;

echo "\n🔧 Closing Stale Attendance Cycles\n";
echo "=====================================\n\n";

// Find all attendance records with open cycles
$staleAttendances = MeetingAttendance::whereNotNull('join_leave_cycles')
    ->get()
    ->filter(function($att) {
        $cycles = $att->join_leave_cycles ?? [];
        $lastCycle = end($cycles);
        return $lastCycle && isset($lastCycle['joined_at']) && !isset($lastCycle['left_at']);
    });

echo "Found " . $staleAttendances->count() . " potentially stale attendance records\n\n";

foreach ($staleAttendances as $att) {
    echo "Checking Session #{$att->session_id} | User #{$att->user_id}... ";

    // Calling isCurrentlyInMeeting will trigger stale cycle detection and auto-close
    $result = $att->isCurrentlyInMeeting() ? '🟢 STILL OPEN' : '🔴 CLOSED';
    echo $result . "\n";

    // Refresh to show updated data
    $att->refresh();
}

echo "\n✅ Done!\n\n";
