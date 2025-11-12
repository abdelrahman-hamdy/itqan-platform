<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Namu\WireChat\Models\Message;
use App\Events\WireChat\MessageCreatedNow;

echo "🧪 Testing WireChat Real-Time Broadcasting\n";
echo "==========================================\n\n";

// Create a test message
$message = Message::create([
    'conversation_id' => 2,
    'sendable_id' => 5,
    'sendable_type' => 'App\Models\User',
    'body' => 'Direct test: ' . now()->toISOString(),
]);

echo "✅ Message created (ID: {$message->id})\n";
echo "   Conversation: {$message->conversation_id}\n";
echo "   From User: {$message->sendable_id}\n";
echo "   Body: {$message->body}\n\n";

// Test broadcasting with different event names
echo "📡 Testing broadcast formats...\n\n";

// Test 1: Our custom event with dot notation
echo "1. Broadcasting with MessageCreatedNow (dot notation)...\n";
$event1 = new MessageCreatedNow($message);
echo "   Event name: " . $event1->broadcastAs() . "\n";
echo "   Channel: private-conversation.{$message->conversation_id}\n";
broadcast($event1)->toOthers();
echo "   ✅ Broadcast sent!\n\n";

// Test 2: Original WireChat event (if it exists)
echo "2. Checking original WireChat event...\n";
if (class_exists(\Namu\WireChat\Events\MessageCreated::class)) {
    $event2 = new \Namu\WireChat\Events\MessageCreated($message);
    echo "   Original event exists!\n";
    echo "   Event broadcasts to queue (not immediate)\n";
    // Don't broadcast this one as it's queued
} else {
    echo "   ❌ Original event class not found\n";
}

echo "\n✅ Test complete!\n";
echo "\nCheck browser console for:\n";
echo "  - 📨 MessageCreated event received!\n";
echo "  - 📨 MessageCreated event (without dot)!\n";