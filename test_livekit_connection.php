<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login user 18 (student from error logs)
$user = App\Models\User::find(18);
$session = App\Models\QuranSession::find(12);

if (! $user || ! $session) {
    exit("User or session not found\n");
}

auth()->login($user);

echo "Testing LiveKit Meeting Connection\n";
echo "=================================\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Session: {$session->id} ({$session->session_type})\n";
echo "Room: {$session->meeting_room_name}\n\n";

try {
    // Test if user can join
    $canJoin = $session->canUserJoinMeeting($user);
    echo 'Can user join: '.($canJoin ? '✅ Yes' : '❌ No')."\n";

    if (! $canJoin) {
        echo "Checking permissions...\n";
        echo '- Is participant: '.($session->isUserParticipant($user) ? 'Yes' : 'No')."\n";
        echo '- Can manage: '.($session->canUserManageMeeting($user) ? 'Yes' : 'No')."\n";
        exit(1);
    }

    // Generate token
    $token = $session->generateParticipantToken($user);
    echo 'Token generated: ✅ '.strlen($token)." characters\n";

    // Test API endpoint directly
    $controller = app(\App\Http\Controllers\UnifiedMeetingController::class);
    $request = new \Illuminate\Http\Request([
        'session_type' => 'quran',
        'session_id' => 12,
    ]);

    $response = $controller->getParticipantToken($request);
    $data = $response->getData(true);

    echo 'API Response: '.($data['success'] ? '✅ Success' : '❌ Failed')."\n";
    if ($data['success']) {
        $responseData = $data['data'];
        echo "- Server URL: {$responseData['server_url']}\n";
        echo "- Room Name: {$responseData['room_name']}\n";
        echo '- Token Length: '.strlen($responseData['access_token'])."\n";
        echo "- User Identity: {$responseData['user_identity']}\n";

        // Create a simple HTML test page
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>LiveKit Test</title>
    <script src="https://unpkg.com/livekit-client@latest/dist/livekit-client.umd.js"></script>
</head>
<body>
    <h1>LiveKit Connection Test</h1>
    <div id="status">Connecting...</div>
    <div id="videos"></div>
    
    <script>
    const { Room, RoomEvent } = LiveKit;
    const room = new Room();
    
    room.on(RoomEvent.Connected, () => {
        document.getElementById("status").innerHTML = "✅ Connected to LiveKit!";
        console.log("Connected to room:", room.name);
    });
    
    room.on(RoomEvent.ConnectionStateChanged, (state) => {
        console.log("Connection state:", state);
        document.getElementById("status").innerHTML = "Connection state: " + state;
    });
    
    room.on(RoomEvent.Disconnected, (reason) => {
        console.log("Disconnected:", reason);
        document.getElementById("status").innerHTML = "❌ Disconnected: " + reason;
    });
    
    // Connect to room
    const serverUrl = "'.$responseData['server_url'].'";
    const token = "'.$responseData['access_token'].'";
    
    console.log("Connecting to:", serverUrl);
    console.log("Token length:", token.length);
    
    room.connect(serverUrl, token)
        .then(() => {
            console.log("Connection successful!");
        })
        .catch((error) => {
            console.error("Connection failed:", error);
            document.getElementById("status").innerHTML = "❌ Connection failed: " + error.message;
        });
    </script>
</body>
</html>';

        file_put_contents('livekit_test.html', $html);
        echo "\n🎯 Test page created: livekit_test.html\n";
        echo "Open this file in your browser to test the LiveKit connection\n";

    } else {
        echo "Error: {$data['message']}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
}
