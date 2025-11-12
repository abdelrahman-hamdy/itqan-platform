<?php

/**
 * Test WireChat Message Broadcasting
 *
 * This script creates a WireChat message and tests if broadcasts work properly
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing WireChat Message Broadcasting\n";
echo "==========================================\n\n";

use App\Models\User;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Events\MessageCreated;

// Get users
$user1 = User::find(1);
$user3 = User::find(3);

if (!$user1 || !$user3) {
    echo "❌ Error: Users not found\n";
    echo "   User 1: " . ($user1 ? "✅" : "❌") . "\n";
    echo "   User 3: " . ($user3 ? "✅" : "❌") . "\n";
    exit(1);
}

echo "👥 Found Users:\n";
echo "   User 1: {$user1->name} (ID: {$user1->id})\n";
echo "   User 3: {$user3->name} (ID: {$user3->id})\n\n";

// Find or create conversation between User 1 and User 3
echo "🔍 Looking for conversation between users...\n";

$conversation = Conversation::whereHas('participants', function ($query) use ($user1) {
    $query->where('participantable_id', $user1->id)
          ->where('participantable_type', User::class);
})->whereHas('participants', function ($query) use ($user3) {
    $query->where('participantable_id', $user3->id)
          ->where('participantable_type', User::class);
})->where('type', 'private')->first();

if (!$conversation) {
    echo "📝 No existing conversation found. Creating new one...\n";

    // Create conversation (force type field)
    $conversation = new Conversation();
    $conversation->type = 'private';
    $conversation->save();

    // Add participants
    Participant::create([
        'conversation_id' => $conversation->id,
        'participantable_id' => $user1->id,
        'participantable_type' => User::class,
        'role' => 'participant',
    ]);

    Participant::create([
        'conversation_id' => $conversation->id,
        'participantable_id' => $user3->id,
        'participantable_type' => User::class,
        'role' => 'participant',
    ]);

    echo "✅ Created new conversation (ID: {$conversation->id})\n\n";
} else {
    echo "✅ Found existing conversation (ID: {$conversation->id})\n\n";
}

// Create test message
echo "📤 Creating test message from User 1 to conversation...\n";

$message = Message::create([
    'conversation_id' => $conversation->id,
    'sendable_id' => $user1->id,
    'sendable_type' => User::class,
    'body' => 'Test message at ' . now()->format('Y-m-d H:i:s'),
    'type' => 'text',
]);

echo "✅ Message created (ID: {$message->id})\n";
echo "   Conversation ID: {$message->conversation_id}\n";
echo "   From: User {$message->sendable_id}\n";
echo "   Body: {$message->body}\n\n";

// Broadcast the message
echo "📡 Broadcasting MessageCreated event...\n";
echo "   Channel: private-conversation.{$conversation->id}\n\n";

try {
    broadcast(new MessageCreated($message))->toOthers();
    echo "✅ Broadcast dispatched successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Broadcast failed: {$e->getMessage()}\n\n";
    exit(1);
}

echo "🎯 Test Instructions:\n";
echo "   1. Open chat in browser as User 3\n";
echo "   2. Navigate to: https://2.itqan-platform.test/chat/{$conversation->id}\n";
echo "   3. Check browser console (F12)\n";
echo "   4. You should see:\n";
echo "      - '✅ Subscribed to private-conversation.{$conversation->id}'\n";
echo "      - '📨 MessageCreated event received'\n";
echo "      - '🔄 Refreshing WireChat component'\n";
echo "   5. Message should appear in the chat!\n\n";

echo "✅ Test complete!\n";
