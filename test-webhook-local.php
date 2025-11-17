#!/usr/bin/env php
<?php
/**
 * LiveKit Webhook Simulator - Local Testing Tool
 *
 * Simulates LiveKit webhooks for testing attendance without ngrok.
 * Usage: php test-webhook-local.php 121 1
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\LiveKitWebhookController;
use App\Models\{QuranSession, AcademicSession, InteractiveCourseSession, User, MeetingAttendanceEvent, MeetingAttendance};

echo "\n╔══════════════════════════════════════════╗\n";
echo "║  LiveKit Webhook Simulator (Local Test) ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// Get session ID and user ID
$sessionId = $argv[1] ?? readline("Enter Session ID: ");
$userId = $argv[2] ?? readline("Enter User ID: ");

// Find session
$session = QuranSession::find($sessionId) ?? AcademicSession::find($sessionId) ?? InteractiveCourseSession::find($sessionId);
if (!$session) die("❌ Session {$sessionId} not found\n");

// Find user  
$user = User::find($userId);
if (!$user) die("❌ User {$userId} not found\n");

echo "✅ Session: {$session->name} (" . class_basename($session) . ")\n";
echo "✅ User: {$user->full_name} ({$user->user_type})\n\n";

// Menu
echo "1. Simulate JOIN\n2. Simulate LEAVE\n3. Full cycle (join → wait → leave)\n4. View attendance data\n0. Exit\n\nChoice: ";
$choice = trim(fgets(STDIN));

$controller = app(LiveKitWebhookController::class);
$roomName = "session-{$sessionId}-" . strtolower(class_basename($session));
$identity = "itqan_{$userId}";

switch($choice) {
    case '1':
        simulateJoin($controller, $session, $user, $roomName, $identity);
        break;
    case '2':
        simulateLeave($controller, $session, $user, $roomName, $identity);
        break;
    case '3':
        simulateJoin($controller, $session, $user, $roomName, $identity);
        echo "⏳ Waiting 10 seconds...\n";
        sleep(10);
        simulateLeave($controller, $session, $user, $roomName, $identity);
        break;
    case '4':
        showAttendance($sessionId, $userId);
        break;
    default:
        exit(0);
}

echo "\n📊 View results: php artisan attendance:debug {$sessionId}\n\n";

function simulateJoin($controller, $session, $user, $roomName, $identity) {
    $eventId = 'EV_'.uniqid();
    $sid = 'PA_'.uniqid();
    $request = Request::create('/webhooks/livekit', 'POST', [
        'event' => 'participant_joined',
        'id' => $eventId,
        'createdAt' => now()->timestamp,
        'room' => ['name' => $roomName, 'sid' => 'RM_'.$session->id, 'num_participants' => 1],
        'participant' => ['sid' => $sid, 'identity' => $identity, 'name' => $user->full_name, 'joinedAt' => now()->timestamp]
    ]);
    $request->headers->set('Content-Type', 'application/json');
    
    try {
        $response = $controller->handleWebhook($request);
        echo ($response->getStatusCode() === 200 ? "✅ JOIN processed\n" : "❌ JOIN failed\n");
        echo "   Event: {$eventId}\n   SID: {$sid}\n";
    } catch(\Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

function simulateLeave($controller, $session, $user, $roomName, $identity) {
    $lastEvent = MeetingAttendanceEvent::where('session_id', $session->id)
        ->where('user_id', $user->id)
        ->where('event_type', 'join')
        ->whereNull('left_at')
        ->latest('event_timestamp')
        ->first();
    
    $sid = $lastEvent->participant_sid ?? 'PA_'.uniqid();
    $joinedAt = $lastEvent ? $lastEvent->event_timestamp->timestamp : now()->subMinutes(5)->timestamp;
    $eventId = 'EV_'.uniqid();
    
    $request = Request::create('/webhooks/livekit', 'POST', [
        'event' => 'participant_left',
        'id' => $eventId,
        'createdAt' => now()->timestamp,
        'room' => ['name' => $roomName, 'sid' => 'RM_'.$session->id, 'num_participants' => 0],
        'participant' => ['sid' => $sid, 'identity' => $identity, 'name' => $user->full_name, 'joinedAt' => $joinedAt]
    ]);
    $request->headers->set('Content-Type', 'application/json');
    
    try {
        $response = $controller->handleWebhook($request);
        $duration = round((now()->timestamp - $joinedAt) / 60, 1);
        echo ($response->getStatusCode() === 200 ? "✅ LEAVE processed\n" : "❌ LEAVE failed\n");
        echo "   Duration: {$duration} minutes\n";
    } catch(\Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

function showAttendance($sessionId, $userId) {
    $att = MeetingAttendance::where('session_id', $sessionId)->where('user_id', $userId)->first();
    echo "\n📊 Attendance Data:\n";
    if ($att) {
        echo "   First join: ".($att->first_join_time ? $att->first_join_time->format('H:i:s') : 'null')."\n";
        echo "   Last leave: ".($att->last_leave_time ? $att->last_leave_time->format('H:i:s') : 'still in meeting')."\n";
        echo "   Duration: {$att->total_duration_minutes} min\n";
        echo "   Cycles: ".(is_array($att->join_leave_cycles) ? count($att->join_leave_cycles) : 0)."\n";
    } else {
        echo "   No record found\n";
    }
    
    $events = MeetingAttendanceEvent::where('session_id', $sessionId)->where('user_id', $userId)->orderBy('event_timestamp')->get();
    echo "\nEvents: {$events->count()}\n";
    foreach($events as $e) {
        echo "   {$e->event_type} @ {$e->event_timestamp->format('H:i:s')} - ".($e->left_at ? $e->left_at->format('H:i:s') : 'open')."\n";
    }
}
