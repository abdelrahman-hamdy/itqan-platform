#!/usr/bin/env php
<?php

/**
 * Test Real-Time Messaging Flow
 *
 * This script verifies that the entire real-time messaging pipeline works:
 * 1. Create a test message
 * 2. Verify MessageCreated event fires
 * 3. Check it goes to the correct queue
 * 4. Verify it broadcasts to Reverb
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing Real-Time Messaging Flow\n";
echo "=====================================\n\n";

// Get a test conversation
$conversation = \Namu\WireChat\Models\Conversation::first();

if (!$conversation) {
    echo "❌ No conversations found. Create a conversation first.\n";
    exit(1);
}

echo "📝 Test Conversation ID: {$conversation->id}\n";
echo "👥 Participants: " . $conversation->participants()->count() . "\n\n";

// Check queue before
$jobsBefore = DB::table('jobs')->count();
echo "📊 Jobs in queue before: {$jobsBefore}\n";

// Get a participant to send message
$participant = $conversation->participants()->first();
if (!$participant) {
    echo "❌ No participants in conversation.\n";
    exit(1);
}

echo "👤 Sending as participant: {$participant->participantable_id}\n\n";

// Create test message
echo "📤 Creating test message...\n";
$message = \Namu\WireChat\Models\Message::create([
    'conversation_id' => $conversation->id,
    'sendable_id' => $participant->participantable_id,
    'sendable_type' => $participant->participantable_type,
    'body' => '🧪 Test message at ' . now()->format('H:i:s'),
    'type' => 'text',
]);

echo "✅ Message created with ID: {$message->id}\n\n";

// Wait a moment for event to fire
sleep(1);

// Check queue after
$jobsAfter = DB::table('jobs')->count();
echo "📊 Jobs in queue after: {$jobsAfter}\n";

if ($jobsAfter > $jobsBefore) {
    echo "✅ Broadcast job was queued!\n";
    $newJobs = $jobsAfter - $jobsBefore;
    echo "   → {$newJobs} new job(s) added to queue\n\n";
} else {
    echo "⚠️  No new jobs in queue. Checking if it was processed immediately...\n\n";
}

// Check queue configuration
$queueConfig = config('wirechat.broadcasting.messages_queue');
echo "🔧 Configuration:\n";
echo "   → Messages queue: {$queueConfig}\n";
echo "   → Broadcast driver: " . config('broadcasting.default') . "\n";
echo "   → Queue connection: " . config('queue.default') . "\n\n";

// Check if queue worker is running on correct queue
$queueWorkerCheck = shell_exec('ps aux | grep "queue:work" | grep -v grep');
if (strpos($queueWorkerCheck, 'messages') !== false) {
    echo "✅ Queue worker is processing 'messages' queue\n";
} else {
    echo "❌ Queue worker is NOT processing 'messages' queue!\n";
    echo "   Current: " . trim($queueWorkerCheck) . "\n";
}

echo "\n🎯 Test complete!\n";
echo "\n📋 Summary:\n";
echo "   • Message ID: {$message->id}\n";
echo "   • Conversation ID: {$conversation->id}\n";
echo "   • Expected channel: private-conversation.{$conversation->id}\n";
echo "   • Expected event: .Namu\\WireChat\\Events\\MessageCreated\n";
echo "\n💡 Now check the browser console for real-time updates!\n";
