<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the meeting API functionality
$session = App\Models\QuranSession::find(12);
$user = App\Models\User::find(3); // Teacher user

if ($session && $user) {
    echo "Testing meeting API for session 12...\n";
    echo "Session: {$session->id} ({$session->session_type})\n";
    echo "User: {$user->name} ({$user->user_type})\n";
    echo "Meeting Room: {$session->meeting_room_name}\n";

    try {
        // Test if user can join
        $canJoin = $session->canUserJoinMeeting($user);
        echo 'Can user join: '.($canJoin ? 'Yes' : 'No')."\n";

        if ($canJoin) {
            // Test token generation
            $token = $session->generateParticipantToken($user);
            echo "Token generated successfully!\n";
            echo 'Token length: '.strlen($token)."\n";

            // Test API response format
            $response = [
                'success' => true,
                'token' => $token,
                'server_url' => config('livekit.server_url'),
                'room_name' => $session->meeting_room_name,
                'participant_name' => $user->first_name.' '.$user->last_name,
                'meeting_id' => $session->meeting_id,
            ];

            echo "API Response would be:\n";
            echo json_encode($response, JSON_PRETTY_PRINT)."\n";

        } else {
            echo "User cannot join meeting - checking permissions...\n";
            echo 'Is participant: '.($session->isUserParticipant($user) ? 'Yes' : 'No')."\n";
            echo 'Can manage: '.($session->canUserManageMeeting($user) ? 'Yes' : 'No')."\n";
        }

    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
        echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    }
} else {
    echo "Session or user not found\n";
}
