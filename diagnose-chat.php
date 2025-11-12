#!/usr/bin/env php
<?php

/**
 * Chat System Diagnostic Tool
 *
 * This script diagnoses why real-time chat isn't working
 * Run: php diagnose-chat.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "\n";
echo "🔍 Chat System Diagnostic\n";
echo "=========================\n\n";

// Test User ID 3 (from console log)
$userId = 3;
$user = User::find($userId);

if (!$user) {
    echo "❌ User ID $userId not found\n";
    exit(1);
}

echo "👤 User Info:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   Academy ID: " . ($user->academy_id ?? 'NULL') . "\n";
echo "   User Type: " . ($user->user_type ?? 'N/A') . "\n\n";

// Check broadcasting configuration
echo "📡 Broadcasting Configuration:\n";
echo "   Driver: " . config('broadcasting.default') . "\n";
echo "   Reverb Host: " . config('broadcasting.connections.reverb.options.host') . "\n";
echo "   Reverb Port: " . config('broadcasting.connections.reverb.options.port') . "\n";
echo "   Reverb Scheme: " . config('broadcasting.connections.reverb.options.scheme') . "\n";
echo "   App Domain: " . config('app.domain') . "\n\n";

// Check if Reverb is reachable
echo "🔌 Reverb Connection Test:\n";
$host = config('broadcasting.connections.reverb.options.host');
$port = config('broadcasting.connections.reverb.options.port');

$socket = @fsockopen($host, $port, $errno, $errstr, 2);
if ($socket) {
    echo "   ✅ Reverb is reachable at $host:$port\n";
    fclose($socket);
} else {
    echo "   ❌ Cannot reach Reverb at $host:$port\n";
    echo "   Error: $errstr ($errno)\n";
}
echo "\n";

// Check queue configuration
echo "📋 Queue Configuration:\n";
echo "   Driver: " . config('queue.default') . "\n";

if (config('queue.default') === 'database') {
    $pendingJobs = DB::table('jobs')->count();
    $failedJobs = DB::table('failed_jobs')->count();
    echo "   Pending Jobs: $pendingJobs\n";
    echo "   Failed Jobs: $failedJobs\n";

    // Check if queue worker is running
    $queueWorkerRunning = shell_exec("ps aux | grep -c 'queue:work' | xargs") > 1;
    if ($queueWorkerRunning) {
        echo "   ✅ Queue worker is running\n";
    } else {
        echo "   ❌ Queue worker is NOT running\n";
        echo "   Run: php artisan queue:work --daemon\n";
    }
}
echo "\n";

// Test channel authorization
echo "🔐 Channel Authorization Test:\n";
$channel = "private-chat.{$userId}";
echo "   Testing channel: $channel\n";

try {
    // Simulate channel authorization
    $authorized = (int) $user->id === (int) $userId;

    if ($authorized) {
        echo "   ✅ User $userId can subscribe to $channel\n";
    } else {
        echo "   ❌ User $userId cannot subscribe to $channel\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check recent messages
echo "💬 Recent Messages:\n";
$recentMessages = DB::table('ch_messages')
    ->where(function ($query) use ($userId) {
        $query->where('from_id', $userId)
              ->orWhere('to_id', $userId);
    })
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

if ($recentMessages->count() > 0) {
    foreach ($recentMessages as $msg) {
        echo "   Message ID: {$msg->id}\n";
        echo "   From: {$msg->from_id} → To: {$msg->to_id}\n";
        echo "   Created: {$msg->created_at}\n";
        echo "   ---\n";
    }
} else {
    echo "   No recent messages found\n";
}
echo "\n";

// Check Reverb process
echo "🔧 Process Check:\n";
$reverbProcess = shell_exec("ps aux | grep 'reverb:start' | grep -v grep");
if ($reverbProcess) {
    echo "   ✅ Reverb process running:\n";
    echo "   " . trim($reverbProcess) . "\n";
} else {
    echo "   ❌ Reverb process NOT running\n";
    echo "   Run: php artisan reverb:start &\n";
}
echo "\n";

$queueProcess = shell_exec("ps aux | grep 'queue:work' | grep -v grep");
if ($queueProcess) {
    echo "   ✅ Queue worker running:\n";
    echo "   " . trim($queueProcess) . "\n";
} else {
    echo "   ❌ Queue worker NOT running\n";
    echo "   Run: php artisan queue:work --daemon &\n";
}
echo "\n";

// Check logs for errors
echo "📜 Recent Errors in Laravel Log:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $errors = shell_exec("tail -100 $logFile | grep -i 'error\|exception\|broadcast' | tail -5");
    if ($errors) {
        echo $errors;
    } else {
        echo "   ✅ No recent errors found\n";
    }
} else {
    echo "   ⚠️  Log file not found\n";
}
echo "\n";

// Final diagnosis
echo "=========================\n";
echo "🎯 Diagnosis Summary:\n\n";

$issues = [];

if (!$socket) {
    $issues[] = "❌ Reverb is not reachable";
}

if (!$queueWorkerRunning && config('queue.default') === 'database') {
    $issues[] = "❌ Queue worker is not running (CRITICAL)";
}

if ($pendingJobs > 10) {
    $issues[] = "⚠️  {$pendingJobs} pending jobs in queue";
}

if ($failedJobs > 0) {
    $issues[] = "⚠️  {$failedJobs} failed jobs in queue";
}

if (empty($issues)) {
    echo "✅ All services appear to be configured correctly!\n\n";
    echo "🔍 Possible Issues:\n";
    echo "   1. Browser might not be receiving events due to:\n";
    echo "      - CORS issues\n";
    echo "      - WebSocket connection dropping\n";
    echo "      - JavaScript not handling events correctly\n\n";
    echo "   2. Events might be broadcasting to wrong channels\n\n";
    echo "   3. Subdomain routing might be affecting authorization\n\n";
    echo "📋 Next Steps:\n";
    echo "   1. Open browser console and check for:\n";
    echo "      - 'Subscribed: private-chat.$userId'\n";
    echo "      - Incoming event logs when message is sent\n\n";
    echo "   2. Send a test message and monitor:\n";
    echo "      tail -f storage/logs/laravel.log\n";
    echo "      tail -f storage/logs/queue-verbose.log\n\n";
    echo "   3. Enable Laravel Telescope for detailed broadcast monitoring\n";
} else {
    echo "Found " . count($issues) . " issue(s):\n\n";
    foreach ($issues as $issue) {
        echo "   $issue\n";
    }
    echo "\n";
    echo "📋 Fix These Issues:\n";
    echo "   1. Start Reverb: php artisan reverb:start &\n";
    echo "   2. Start Queue Worker: php artisan queue:work --daemon &\n";
    echo "   3. Monitor: ./restart-chat-services.sh\n";
}

echo "\n";
