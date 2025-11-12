<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking READY Sessions ===".PHP_EOL;
$readySessions = \App\Models\QuranSession::where('status', \App\Enums\SessionStatus::READY)->get();
echo "Total READY sessions: ".$readySessions->count().PHP_EOL.PHP_EOL;

if ($readySessions->count() > 0) {
    foreach ($readySessions as $session) {
        echo "Session ID: ".$session->id.PHP_EOL;
        echo "  Scheduled At: ".$session->scheduled_at.PHP_EOL;
        echo "  Status: ".$session->status->value.PHP_EOL;
        echo "  Meeting Room Name: ".($session->meeting_room_name ?? 'NULL').PHP_EOL;
        echo "  Created At: ".$session->created_at.PHP_EOL;
        echo PHP_EOL;
    }
}

echo "=== Checking SCHEDULED Sessions ===>".PHP_EOL;
$scheduledSessions = \App\Models\QuranSession::where('status', \App\Enums\SessionStatus::SCHEDULED)
    ->orderBy('scheduled_at', 'asc')
    ->get();
echo "Total SCHEDULED sessions: ".$scheduledSessions->count().PHP_EOL.PHP_EOL;

if ($scheduledSessions->count() > 0) {
    echo "First 3 scheduled sessions:".PHP_EOL;
    foreach ($scheduledSessions->take(3) as $session) {
        $now = now();
        $scheduledAt = $session->scheduled_at;

        echo "Session ID: ".$session->id.PHP_EOL;
        echo "  Scheduled At: ".$scheduledAt.PHP_EOL;
        echo "  Current Time: ".$now.PHP_EOL;
        echo "  Status: ".$session->status->value.PHP_EOL;

        // Get status display data
        $statusData = $session->getStatusDisplayData();
        echo "  Prep Minutes: ".$statusData['preparation_minutes'].PHP_EOL;

        // Calculate preparation window
        $prepMinutes = $statusData['preparation_minutes'];
        $preparationTime = $scheduledAt->copy()->subMinutes($prepMinutes);
        echo "  Preparation Time: ".$preparationTime.PHP_EOL;
        echo "  Should be READY?: ".($now->greaterThanOrEqualTo($preparationTime) ? 'YES' : 'NO').PHP_EOL;

        $prepMessage = getMeetingPreparationMessage($session);
        echo "  Prep Type: ".$prepMessage['type'].PHP_EOL;
        echo "  Prep Message: ".$prepMessage['message'].PHP_EOL;
        echo PHP_EOL;
    }
}

echo "=== Checking Session Status Service ===".PHP_EOL;
$sessionStatusService = app(\App\Services\SessionStatusService::class);

// Get sessions that should transition
$transitionSessions = \App\Models\QuranSession::whereIn('status', [
    \App\Enums\SessionStatus::SCHEDULED,
    \App\Enums\SessionStatus::READY,
    \App\Enums\SessionStatus::ONGOING,
])->with(['academy', 'circle', 'individualCircle'])->get();

echo "Sessions that could transition: ".$transitionSessions->count().PHP_EOL;

foreach ($transitionSessions->take(3) as $session) {
    echo "Session ID: ".$session->id." (Status: ".$session->status->value.")".PHP_EOL;
    echo "  Should be ready?: ".($sessionStatusService->shouldBeReady($session) ? 'YES' : 'NO').PHP_EOL;
    echo "  Should be ongoing?: ".($sessionStatusService->shouldBeOngoing($session) ? 'YES' : 'NO').PHP_EOL;
    echo "  Should be completed?: ".($sessionStatusService->shouldBeCompleted($session) ? 'YES' : 'NO').PHP_EOL;
    echo PHP_EOL;
}
