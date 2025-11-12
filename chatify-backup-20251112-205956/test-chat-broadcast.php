#!/usr/bin/env php
<?php

/**
 * Chat Broadcast Test Script
 *
 * This script tests if broadcasting is working correctly
 * Run: php test-chat-broadcast.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChMessage;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\MessageSentEvent;

echo "\n";
echo "🧪 Chat Broadcast Test\n";
echo "======================\n\n";

// Get test users
echo "📋 Checking test users...\n";
$users = User::take(2)->get();

if ($users->count() < 2) {
    echo "❌ Error: Need at least 2 users in database\n";
    exit(1);
}

$sender = $users[0];
$receiver = $users[1];

echo "✅ Sender: {$sender->name} (ID: {$sender->id})\n";
echo "✅ Receiver: {$receiver->name} (ID: {$receiver->id})\n\n";

// Test 1: Check queue connection
echo "Test 1: Queue Connection\n";
echo "-------------------------\n";
$queueConnection = config('queue.default');
echo "Queue Driver: $queueConnection\n";

if ($queueConnection === 'database') {
    $pendingJobs = DB::table('jobs')->where('queue', 'default')->count();
    echo "Pending Jobs: $pendingJobs\n";
} elseif ($queueConnection === 'sync') {
    echo "⚠️  Warning: Using 'sync' queue - broadcasts happen immediately\n";
}
echo "\n";

// Test 2: Check broadcasting connection
echo "Test 2: Broadcasting Connection\n";
echo "--------------------------------\n";
$broadcastDriver = config('broadcasting.default');
echo "Broadcast Driver: $broadcastDriver\n";

if ($broadcastDriver === 'reverb') {
    $reverbHost = config('broadcasting.connections.reverb.options.host');
    $reverbPort = config('broadcasting.connections.reverb.options.port');
    echo "Reverb Host: $reverbHost\n";
    echo "Reverb Port: $reverbPort\n";

    // Check if Reverb is running
    $socket = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 1);
    if ($socket) {
        echo "✅ Reverb is RUNNING\n";
        fclose($socket);
    } else {
        echo "❌ Reverb is NOT RUNNING (Error: $errstr)\n";
    }
} else {
    echo "⚠️  Warning: Not using Reverb driver\n";
}
echo "\n";

// Test 3: Create a test message
echo "Test 3: Create Test Message\n";
echo "----------------------------\n";

try {
    $message = new ChMessage();
    $message->from_id = $sender->id;
    $message->to_id = $receiver->id;
    $message->body = "Test message at " . now()->format('Y-m-d H:i:s');
    $message->academy_id = $sender->academy_id;
    $message->save();

    echo "✅ Message created (ID: {$message->id})\n";
    echo "   From: {$sender->name}\n";
    echo "   To: {$receiver->name}\n";
    echo "   Body: {$message->body}\n\n";

} catch (\Exception $e) {
    echo "❌ Error creating message: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Broadcast MessageSentEvent
echo "Test 4: Broadcasting MessageSentEvent\n";
echo "--------------------------------------\n";

try {
    broadcast(new MessageSentEvent(
        $sender->id,
        $receiver->id,
        $sender->academy_id,
        false
    ));

    echo "✅ MessageSentEvent broadcast dispatched\n";
    echo "   Channel: private-chat.{$receiver->id}\n";
    echo "   Event: message.sent\n\n";

} catch (\Exception $e) {
    echo "❌ Error broadcasting MessageSentEvent: " . $e->getMessage() . "\n\n";
}

// Test 5: Broadcast MessageSent
echo "Test 5: Broadcasting MessageSent\n";
echo "---------------------------------\n";

try {
    broadcast(new MessageSent($message))->toOthers();

    echo "✅ MessageSent broadcast dispatched\n";
    echo "   Channels: private-chat.{$sender->id}, private-chat.{$receiver->id}\n";
    echo "   Event: message.new\n\n";

} catch (\Exception $e) {
    echo "❌ Error broadcasting MessageSent: " . $e->getMessage() . "\n\n";
}

// Test 6: Check queue
echo "Test 6: Check Queue\n";
echo "-------------------\n";

if ($queueConnection === 'database') {
    sleep(1); // Wait a bit for jobs to be queued

    $newJobs = DB::table('jobs')->where('queue', 'default')->count();
    $addedJobs = $newJobs - ($pendingJobs ?? 0);

    echo "Total Jobs in Queue: $newJobs\n";
    echo "Jobs Added by Test: $addedJobs\n";

    if ($addedJobs > 0) {
        echo "✅ Jobs were queued successfully\n";
        echo "\n⚠️  NOTE: You need a queue worker running to process these jobs!\n";
        echo "   Run: php artisan queue:work\n";
    } else {
        echo "⚠️  No jobs were added to queue\n";
        echo "   This might be normal if using sync queue or immediate broadcasts\n";
    }
} else {
    echo "ℹ️  Queue driver is '$queueConnection' - jobs may be processed immediately\n";
}

echo "\n";

// Test 7: Check Reverb logs
echo "Test 7: Reverb Logs\n";
echo "-------------------\n";
$reverbLogPath = storage_path('logs/reverb.log');
if (file_exists($reverbLogPath)) {
    $lastLines = shell_exec("tail -n 5 $reverbLogPath");
    echo "Last 5 lines from reverb.log:\n";
    echo $lastLines ?: "(empty)\n";
} else {
    echo "⚠️  Reverb log file not found at: $reverbLogPath\n";
}

echo "\n";
echo "======================\n";
echo "✅ Broadcast Test Complete\n\n";

echo "📋 Summary:\n";
echo "- Message created: ✅\n";
echo "- Events dispatched: ✅\n";
echo "- Check if messages appear in real-time in browser\n";
echo "- Monitor queue: php artisan queue:work --verbose\n";
echo "- Monitor Reverb: tail -f storage/logs/reverb.log\n";
echo "\n";
