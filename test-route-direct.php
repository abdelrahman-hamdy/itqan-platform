<?php

/**
 * Test if the route is actually accessible via HTTP
 * This simulates what the browser does
 */

// Get the base URL from .env
$baseUrl = 'https://itqan-academy.itqan-platform.test'; // Change this to your actual URL

echo "🧪 Testing LiveKit Routes via HTTP\n";
echo str_repeat("=", 70) . "\n\n";

// Test URLs
$tests = [
    [
        'name' => 'GET Permissions',
        'url' => $baseUrl . '/livekit/rooms/permissions?room_name=test-room',
        'method' => 'GET',
    ],
    [
        'name' => 'POST Mute All Students',
        'url' => $baseUrl . '/livekit/participants/mute-all-students',
        'method' => 'POST',
        'data' => json_encode(['room_name' => 'test-room', 'muted' => true]),
    ],
    [
        'name' => 'POST Disable Camera',
        'url' => $baseUrl . '/livekit/participants/disable-all-students-camera',
        'method' => 'POST',
        'data' => json_encode(['room_name' => 'test-room', 'disabled' => true]),
    ],
];

foreach ($tests as $test) {
    echo "Testing: {$test['name']}\n";
    echo "URL: {$test['url']}\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($test['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $test['data'] ?? '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Extract status line
    $statusLine = strtok($response, "\n");

    // Color code based on status
    if ($httpCode === 200) {
        echo "✅ Status: {$httpCode} - OK\n";
    } elseif ($httpCode === 401 || $httpCode === 403) {
        echo "⚠️  Status: {$httpCode} - Auth required (route exists!)\n";
    } elseif ($httpCode === 404) {
        echo "❌ Status: {$httpCode} - NOT FOUND (route doesn't exist!)\n";
    } else {
        echo "⚡ Status: {$httpCode}\n";
    }

    echo "{$statusLine}\n";
    echo str_repeat("-", 70) . "\n\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "💡 Interpretation:\n";
echo "  ✅ 200 = Route works, authenticated\n";
echo "  ⚠️  401/403 = Route exists but needs auth (GOOD!)\n";
echo "  ❌ 404 = Route doesn't exist (BAD!)\n";
echo "  ⚡ 419 = CSRF token missing (route exists)\n";
echo "  ⚡ 500 = Server error (route exists but crashes)\n";
echo "\n";
