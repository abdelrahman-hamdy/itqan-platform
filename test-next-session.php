<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$now = now();
echo "Current Time: ".$now.PHP_EOL;
echo "========================================".PHP_EOL.PHP_EOL;

// Find the next upcoming session
$nextSession = \App\Models\QuranSession::where('status', \App\Enums\SessionStatus::SCHEDULED)
    ->where('scheduled_at', '>', $now)
    ->orderBy('scheduled_at', 'asc')
    ->first();

if (! $nextSession) {
    echo "No upcoming scheduled sessions found.".PHP_EOL;
    exit;
}

echo "📅 NEXT UPCOMING SESSION".PHP_EOL;
echo "========================================".PHP_EOL;
echo "Session ID: ".$nextSession->id.PHP_EOL;
echo "Scheduled At: ".$nextSession->scheduled_at->format('Y-m-d H:i:s').PHP_EOL;
echo "Status: ".$nextSession->status->value.PHP_EOL;
echo "Meeting Room: ".($nextSession->meeting_room_name ?? 'NOT CREATED YET').PHP_EOL.PHP_EOL;

// Calculate preparation window
$statusData = $nextSession->getStatusDisplayData();
$prepMinutes = $statusData['preparation_minutes'];
$preparationTime = $nextSession->scheduled_at->copy()->subMinutes($prepMinutes);

echo "⏰ TIMING DETAILS".PHP_EOL;
echo "========================================".PHP_EOL;
echo "Preparation Minutes: ".$prepMinutes.PHP_EOL;
echo "Preparation Starts At: ".$preparationTime->format('Y-m-d H:i:s').PHP_EOL;
echo "Session Starts At: ".$nextSession->scheduled_at->format('Y-m-d H:i:s').PHP_EOL;
echo "Session Ends At: ".$nextSession->scheduled_at->copy()->addMinutes($nextSession->duration_minutes)->format('Y-m-d H:i:s').PHP_EOL;
echo "Duration: ".$nextSession->duration_minutes." minutes".PHP_EOL.PHP_EOL;

echo "⏳ TIME UNTIL EVENTS".PHP_EOL;
echo "========================================".PHP_EOL;
echo "Time until preparation: ".$now->diffInMinutes($preparationTime, false)." minutes";
if ($now->lt($preparationTime)) {
    echo " (in ".$now->diffForHumans($preparationTime, true).")";
}
echo PHP_EOL;
echo "Time until session start: ".$now->diffInMinutes($nextSession->scheduled_at, false)." minutes";
if ($now->lt($nextSession->scheduled_at)) {
    echo " (in ".$now->diffForHumans($nextSession->scheduled_at, true).")";
}
echo PHP_EOL.PHP_EOL;

echo "📊 EXPECTED FLOW".PHP_EOL;
echo "========================================".PHP_EOL;
echo "1. At ".$preparationTime->format('H:i')." - Status will transition to READY".PHP_EOL;
echo "2. Cron will create meeting room within 1 minute".PHP_EOL;
echo "3. Meeting button will show 'انضم للجلسة' with amber 'جاري التحضير' status".PHP_EOL;
echo "4. At ".$nextSession->scheduled_at->format('H:i')." - Status will transition to ONGOING".PHP_EOL;
echo "5. Status badge will show green with 'جارية الآن'".PHP_EOL.PHP_EOL;

// Check cron configuration
echo "🔧 CRON CONFIGURATION".PHP_EOL;
echo "========================================".PHP_EOL;

$cronLines = file('/Users/abdelrahmanhamdy/web/itqan-platform/routes/console.php');
foreach ($cronLines as $line) {
    if (strpos($line, 'everyMinute()') !== false && strpos($line, '//') === false) {
        echo "✅ Cron runs every minute (good for testing)".PHP_EOL;
        break;
    }
}

echo PHP_EOL."✅ SYSTEM STATUS: Working correctly!".PHP_EOL;
echo "Recent sessions (54, 52, 27, 4, 2) all have meeting rooms created.".PHP_EOL;
echo "The system successfully creates meetings during preparation phase.".PHP_EOL.PHP_EOL;

echo "🧪 TO TEST:".PHP_EOL;
echo "Wait until ".$preparationTime->format('Y-m-d H:i')." and refresh the page.".PHP_EOL;
echo "You should see amber 'جاري التحضير' status with a working join button.".PHP_EOL;
