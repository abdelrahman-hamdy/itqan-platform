<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$now = now();
echo "Current Time: ".$now.PHP_EOL;
echo "Timezone: ".config('app.timezone').PHP_EOL.PHP_EOL;

echo "=== Checking SCHEDULED Sessions in Preparation Window ===".PHP_EOL;
$scheduledSessions = \App\Models\QuranSession::where('status', \App\Enums\SessionStatus::SCHEDULED)
    ->whereNotNull('scheduled_at')
    ->orderBy('scheduled_at', 'asc')
    ->get();

echo "Total SCHEDULED sessions: ".$scheduledSessions->count().PHP_EOL.PHP_EOL;

$sessionStatusService = app(\App\Services\SessionStatusService::class);

$shouldBeReady = [];
$inPrepWindow = [];

foreach ($scheduledSessions as $session) {
    $scheduledAt = $session->scheduled_at;
    $statusData = $session->getStatusDisplayData();
    $prepMinutes = $statusData['preparation_minutes'];
    $preparationTime = $scheduledAt->copy()->subMinutes($prepMinutes);

    // Check if in preparation window (frontend would show "preparing")
    $isInPrepWindow = $now->gte($preparationTime) && $now->lt($scheduledAt);

    // Check if should transition to READY (backend check)
    $shouldTransition = $sessionStatusService->shouldTransitionToReady($session);

    if ($isInPrepWindow || $shouldTransition) {
        echo "Session ID: ".$session->id.PHP_EOL;
        echo "  Scheduled At: ".$scheduledAt.PHP_EOL;
        echo "  Preparation Time: ".$preparationTime.PHP_EOL;
        echo "  In Prep Window?: ".($isInPrepWindow ? 'YES' : 'NO').PHP_EOL;
        echo "  Should Transition to READY?: ".($shouldTransition ? 'YES' : 'NO').PHP_EOL;

        if ($isInPrepWindow) {
            $inPrepWindow[] = $session->id;
        }
        if ($shouldTransition) {
            $shouldBeReady[] = $session->id;
        }
        echo PHP_EOL;
    }
}

echo "=== Summary ===".PHP_EOL;
echo "Sessions in preparation window (frontend shows 'preparing'): ".count($inPrepWindow).PHP_EOL;
if (count($inPrepWindow) > 0) {
    echo "  IDs: ".implode(', ', $inPrepWindow).PHP_EOL;
}
echo "Sessions that should transition to READY (backend check): ".count($shouldBeReady).PHP_EOL;
if (count($shouldBeReady) > 0) {
    echo "  IDs: ".implode(', ', $shouldBeReady).PHP_EOL;
}

echo PHP_EOL."=== Testing Manual Transition ===".PHP_EOL;
if (count($shouldBeReady) > 0) {
    echo "Testing transition for session ID: ".$shouldBeReady[0].PHP_EOL;
    $testSession = \App\Models\QuranSession::find($shouldBeReady[0]);

    echo "Before transition:".PHP_EOL;
    echo "  Status: ".$testSession->status->value.PHP_EOL;
    echo "  Meeting Room Name: ".($testSession->meeting_room_name ?? 'NULL').PHP_EOL;

    try {
        $result = $sessionStatusService->transitionToReady($testSession);
        echo "Transition result: ".($result ? 'SUCCESS' : 'FAILED').PHP_EOL;

        $testSession->refresh();
        echo "After transition:".PHP_EOL;
        echo "  Status: ".$testSession->status->value.PHP_EOL;
        echo "  Meeting Room Name: ".($testSession->meeting_room_name ?? 'NULL').PHP_EOL;

    } catch (\Exception $e) {
        echo "Error during transition: ".$e->getMessage().PHP_EOL;
    }
} else {
    echo "No sessions ready for transition right now.".PHP_EOL;
}
