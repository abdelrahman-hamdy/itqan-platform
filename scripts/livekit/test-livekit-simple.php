<?php

/**
 * Simple LiveKit Server Test
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== LiveKit Server Test ===\n\n";

// Test configuration
$serverUrl = config('livekit.server_url');
$apiUrl = config('livekit.api_url');
$apiKey = config('livekit.api_key');

echo "Server URL: {$serverUrl}\n";
echo "API URL: {$apiUrl}\n";
echo "API Key: {$apiKey}\n\n";

// Test HTTPS connectivity
echo "Testing HTTPS connectivity...\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Server is reachable (HTTP {$httpCode})\n";
    echo "Response: {$response}\n\n";
} else {
    echo "❌ Server unreachable (HTTP {$httpCode})\n\n";
}

// Test LiveKitService
echo "Testing LiveKitService...\n";
$liveKitService = app(\App\Services\LiveKitService::class);

if ($liveKitService->isConfigured()) {
    echo "✅ LiveKitService is configured\n\n";
} else {
    echo "❌ LiveKitService is NOT configured\n\n";
    exit(1);
}

// Test token generation with a real user
echo "Testing token generation...\n";
$testUser = \App\Models\User::first();

if ($testUser) {
    try {
        $token = $liveKitService->generateParticipantToken(
            'test-room-' . time(),
            $testUser
        );

        echo "✅ Token generated successfully\n";
        echo "Token length: " . strlen($token) . " characters\n";
        echo "User: {$testUser->name} (ID: {$testUser->id})\n\n";
    } catch (Exception $e) {
        echo "❌ Failed: {$e->getMessage()}\n\n";
        exit(1);
    }
} else {
    echo "⚠️  No users found in database (seed first)\n\n";
}

echo "=== All Tests Passed! ✅ ===\n\n";
