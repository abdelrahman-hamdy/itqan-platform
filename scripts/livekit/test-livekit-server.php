<?php

/**
 * LiveKit Server Integration Test
 *
 * This script tests the connection to the LiveKit server and verifies
 * that tokens can be generated correctly.
 */

require __DIR__.'/vendor/autoload.php';

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;

// Load environment variables
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== LiveKit Server Integration Test ===\n\n";

// 1. Check configuration
echo "1. Checking configuration...\n";
$serverUrl = config('livekit.server_url');
$apiUrl = config('livekit.api_url');
$apiKey = config('livekit.api_key');
$apiSecret = config('livekit.api_secret');

echo "   Server URL: {$serverUrl}\n";
echo "   API URL: {$apiUrl}\n";
echo "   API Key: {$apiKey}\n";
echo "   API Secret: " . substr($apiSecret, 0, 10) . "...\n";

if (empty($apiKey) || empty($apiSecret)) {
    echo "   ❌ FAILED: API credentials not configured\n";
    exit(1);
}
echo "   ✅ Configuration loaded\n\n";

// 2. Test server connectivity
echo "2. Testing server connectivity...\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    echo "   ✅ Server is reachable (HTTP {$httpCode})\n";
    echo "   Response: {$response}\n\n";
} else {
    echo "   ❌ FAILED: Server unreachable (HTTP {$httpCode})\n";
    if ($error) {
        echo "   Error: {$error}\n";
    }
    exit(1);
}

// 3. Test token generation
echo "3. Testing token generation...\n";
try {
    $tokenOptions = new AccessTokenOptions();
    $tokenOptions->identity = 'test-user-' . time();

    $videoGrant = new VideoGrant();
    $videoGrant->roomJoin = true;
    $videoGrant->room = 'test-room-' . time();

    $token = new AccessToken($apiKey, $apiSecret);
    $token->init($tokenOptions);
    $token->setGrant($videoGrant);

    $jwt = $token->toJwt();

    echo "   ✅ Token generated successfully\n";
    echo "   Token length: " . strlen($jwt) . " characters\n";
    echo "   Token preview: " . substr($jwt, 0, 50) . "...\n\n";
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    exit(1);
}

// 4. Test LiveKitService (if exists)
echo "4. Testing LiveKitService...\n";
try {
    $liveKitService = app(\App\Services\LiveKitService::class);

    $testRoom = 'integration-test-' . time();
    $testUser = [
        'id' => 999,
        'name' => 'Test User',
    ];

    $result = $liveKitService->generateAccessToken($testRoom, $testUser);

    if (!empty($result['token']) && !empty($result['url'])) {
        echo "   ✅ LiveKitService working correctly\n";
        echo "   Room: {$testRoom}\n";
        echo "   Token generated: Yes\n";
        echo "   URL: {$result['url']}\n\n";
    } else {
        echo "   ❌ FAILED: Invalid response from LiveKitService\n";
        print_r($result);
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ FAILED: {$e->getMessage()}\n";
    echo "   Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}

echo "=== All Tests Passed! ✅ ===\n\n";
echo "Your LiveKit server is configured correctly and ready to use.\n";
echo "Next steps:\n";
echo "  1. Test video meetings in your application\n";
echo "  2. Configure recording webhooks (optional)\n";
echo "  3. Monitor server logs: docker logs -f livekit-server\n\n";
