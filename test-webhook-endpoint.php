<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "\n🧪 Testing LiveKit Webhook Endpoint\n";
echo "=====================================\n\n";

// Test webhook endpoint URL - CORRECT PATH IS /webhooks/livekit (not /api/livekit)
$webhookUrl = env('APP_URL', 'http://itqan-platform.test') . '/webhooks/livekit';

echo "📍 Webhook URL: $webhookUrl\n";
echo "⚠️  IMPORTANT: This is the CORRECT webhook URL path!\n";
echo "   Previous attempts may have used /api/livekit (WRONG!)\n\n";

// Sample webhook payload (participant_joined event)
$samplePayload = [
    'event' => 'participant_joined',
    'room' => [
        'sid' => 'RM_test123',
        'name' => 'session-96',
        'num_participants' => 1,
        'creation_time' => time(),
    ],
    'participant' => [
        'sid' => 'PA_test456',
        'identity' => '5_ameer_maher',
        'name' => 'Ameer Maher',
        'state' => 'ACTIVE',
        'joined_at' => time(),
    ],
];

echo "📦 Sample Payload:\n";
echo json_encode($samplePayload, JSON_PRETTY_PRINT) . "\n\n";

// Send test webhook request
echo "🚀 Sending test webhook request...\n\n";

try {
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($samplePayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Test-Request: true',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    echo "📥 Response:\n";
    echo "   HTTP Code: $httpCode\n";
    echo "   Body: $response\n";

    if ($error) {
        echo "   ❌ Error: $error\n";
    }

    echo "\n";

    if ($httpCode === 200) {
        echo "✅ Webhook endpoint is reachable and responding!\n\n";
    } else {
        echo "⚠️ Webhook endpoint responded with status $httpCode\n\n";
    }

    echo "📋 Now check Laravel logs for the webhook entry:\n";
    echo "   tail -f storage/logs/laravel.log | grep 'WEBHOOK ENDPOINT HIT'\n\n";

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n\n";
}

echo "🔍 Checking recent webhook logs...\n\n";

// Read last 50 lines of log file
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logLines = file($logFile);
    $recentLines = array_slice($logLines, -100);

    $webhookLogs = array_filter($recentLines, function($line) {
        return stripos($line, 'WEBHOOK ENDPOINT HIT') !== false
            || stripos($line, 'participant_joined') !== false
            || stripos($line, 'participant_left') !== false;
    });

    if (empty($webhookLogs)) {
        echo "❌ No webhook logs found in recent activity\n";
        echo "   This means either:\n";
        echo "   1. LiveKit is not configured to send webhooks to this server\n";
        echo "   2. The webhook URL is incorrect\n";
        echo "   3. The endpoint is not reachable from LiveKit Cloud\n\n";
    } else {
        echo "✅ Found webhook activity:\n";
        foreach ($webhookLogs as $line) {
            echo "   " . trim($line) . "\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️ Log file not found: $logFile\n\n";
}

echo "📝 Next Steps:\n";
echo "==================\n";
echo "1. Run this script: php test-webhook-endpoint.php\n";
echo "2. Join a LiveKit meeting as user (ID: 5)\n";
echo "3. Check logs: tail -f storage/logs/laravel.log | grep 'WEBHOOK'\n";
echo "4. If no logs appear when joining, webhooks are not configured in LiveKit Cloud\n\n";

echo "🔧 LiveKit Cloud Webhook Configuration:\n";
echo "=========================================\n";
echo "Go to: https://cloud.livekit.io/projects/test-rn3dlic1/settings\n";
echo "Add webhook URL: " . env('APP_URL', 'http://itqan-platform.test') . "/webhooks/livekit\n";
echo "\n";
echo "🚨 CRITICAL: The correct path is /webhooks/livekit (NOT /api/livekit)\n";
echo "   If you have /api/livekit configured, that is WRONG and must be updated!\n";
echo "\n";
echo "Events to enable:\n";
echo "  - participant_joined\n";
echo "  - participant_left\n";
echo "  - room_started\n";
echo "  - room_finished\n\n";

echo "✅ Done!\n\n";
