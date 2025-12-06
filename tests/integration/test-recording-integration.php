#!/usr/bin/env php
<?php

/**
 * Recording Integration Test Script
 *
 * Tests the complete recording feature integration:
 * 1. RecordingService integration
 * 2. InteractiveCourseSession RecordingCapable implementation
 * 3. Routes configuration
 * 4. Controller methods
 *
 * Run: php test-recording-integration.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Route;
use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use App\Services\RecordingService;
use App\Contracts\RecordingCapable;
use App\Traits\HasRecording;

echo "=== Recording Integration Test ===\n\n";

// Test 1: Check if InteractiveCourseSession implements RecordingCapable
echo "Test 1: InteractiveCourseSession implements RecordingCapable\n";
$reflection = new ReflectionClass(InteractiveCourseSession::class);
$implementsInterface = $reflection->implementsInterface(RecordingCapable::class);

if ($implementsInterface) {
    echo "✅ PASS: InteractiveCourseSession implements RecordingCapable\n";
} else {
    echo "❌ FAIL: InteractiveCourseSession does NOT implement RecordingCapable\n";
}

// Test 2: Check if HasRecording trait is used
echo "\nTest 2: InteractiveCourseSession uses HasRecording trait\n";
$traits = class_uses(InteractiveCourseSession::class);
$usesTrait = in_array(HasRecording::class, $traits);

if ($usesTrait) {
    echo "✅ PASS: InteractiveCourseSession uses HasRecording trait\n";
} else {
    echo "❌ FAIL: InteractiveCourseSession does NOT use HasRecording trait\n";
}

// Test 3: Check RecordingService exists and has required methods
echo "\nTest 3: RecordingService has required methods\n";
$serviceMethods = get_class_methods(RecordingService::class);
$requiredMethods = ['startRecording', 'stopRecording', 'processEgressWebhook'];
$allMethodsExist = true;

foreach ($requiredMethods as $method) {
    if (in_array($method, $serviceMethods)) {
        echo "  ✅ {$method}() exists\n";
    } else {
        echo "  ❌ {$method}() MISSING\n";
        $allMethodsExist = false;
    }
}

if ($allMethodsExist) {
    echo "✅ PASS: RecordingService has all required methods\n";
} else {
    echo "❌ FAIL: RecordingService is missing methods\n";
}

// Test 4: Check SessionRecording model methods
echo "\nTest 4: SessionRecording model helper methods\n";
$recordingMethods = get_class_methods(SessionRecording::class);
$requiredRecordingMethods = [
    'isCompleted',
    'isRecording',
    'markAsCompleted',
    'markAsFailed',
    'getDownloadUrl',
    'getStreamUrl'
];
$allRecordingMethodsExist = true;

foreach ($requiredRecordingMethods as $method) {
    if (in_array($method, $recordingMethods)) {
        echo "  ✅ {$method}() exists\n";
    } else {
        echo "  ❌ {$method}() MISSING\n";
        $allRecordingMethodsExist = false;
    }
}

if ($allRecordingMethodsExist) {
    echo "✅ PASS: SessionRecording has all helper methods\n";
} else {
    echo "❌ FAIL: SessionRecording is missing methods\n";
}

// Test 5: Check required routes exist
echo "\nTest 5: Required routes exist\n";

// Boot Laravel to load routes
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$requiredRoutes = [
    'api.recordings.start',
    'api.recordings.stop',
    'api.recordings.session',
    'recordings.download',
    'recordings.stream',
];

$allRoutesExist = true;

foreach ($requiredRoutes as $routeName) {
    if (Route::has($routeName)) {
        echo "  ✅ {$routeName}\n";
    } else {
        echo "  ❌ {$routeName} MISSING\n";
        $allRoutesExist = false;
    }
}

if ($allRoutesExist) {
    echo "✅ PASS: All required routes exist\n";
} else {
    echo "❌ FAIL: Some routes are missing\n";
}

// Test 6: Check controller methods exist
echo "\nTest 6: InteractiveCourseRecordingController methods\n";
$controllerClass = 'App\Http\Controllers\InteractiveCourseRecordingController';
$controllerMethods = get_class_methods($controllerClass);
$requiredControllerMethods = [
    'startRecording',
    'stopRecording',
    'getSessionRecordings',
    'downloadRecording',
    'streamRecording',
    'deleteRecording'
];
$allControllerMethodsExist = true;

foreach ($requiredControllerMethods as $method) {
    if (in_array($method, $controllerMethods)) {
        echo "  ✅ {$method}() exists\n";
    } else {
        echo "  ❌ {$method}() MISSING\n";
        $allControllerMethodsExist = false;
    }
}

if ($allControllerMethodsExist) {
    echo "✅ PASS: Controller has all required methods\n";
} else {
    echo "❌ FAIL: Controller is missing methods\n";
}

// Test 7: Check webhook route exists
echo "\nTest 7: LiveKit webhook routes\n";
$webhookRoutes = ['webhooks.livekit', 'webhooks.livekit.health'];
$allWebhookRoutesExist = true;

foreach ($webhookRoutes as $routeName) {
    if (Route::has($routeName)) {
        echo "  ✅ {$routeName}\n";
    } else {
        echo "  ❌ {$routeName} MISSING\n";
        $allWebhookRoutesExist = false;
    }
}

if ($allWebhookRoutesExist) {
    echo "✅ PASS: Webhook routes exist\n";
} else {
    echo "❌ FAIL: Some webhook routes are missing\n";
}

// Test 8: Check LiveKitService has recording methods
echo "\nTest 8: LiveKitService recording methods\n";
$liveKitServiceClass = 'App\Services\LiveKitService';
$liveKitMethods = get_class_methods($liveKitServiceClass);
$requiredLiveKitMethods = ['startRecording', 'stopRecording'];
$allLiveKitMethodsExist = true;

foreach ($requiredLiveKitMethods as $method) {
    if (in_array($method, $liveKitMethods)) {
        echo "  ✅ {$method}() exists\n";
    } else {
        echo "  ❌ {$method}() MISSING\n";
        $allLiveKitMethodsExist = false;
    }
}

if ($allLiveKitMethodsExist) {
    echo "✅ PASS: LiveKitService has recording methods\n";
} else {
    echo "❌ FAIL: LiveKitService is missing methods\n";
}

// Test 9: Check database table exists
echo "\nTest 9: Database table 'session_recordings' exists\n";
try {
    $tableExists = \Illuminate\Support\Facades\Schema::hasTable('session_recordings');

    if ($tableExists) {
        echo "✅ PASS: session_recordings table exists\n";

        // Check required columns
        $requiredColumns = [
            'id', 'recordable_type', 'recordable_id', 'recording_id',
            'meeting_room', 'status', 'file_path', 'file_name', 'file_size'
        ];

        echo "  Checking columns:\n";
        $allColumnsExist = true;
        foreach ($requiredColumns as $column) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('session_recordings', $column)) {
                echo "    ✅ {$column}\n";
            } else {
                echo "    ❌ {$column} MISSING\n";
                $allColumnsExist = false;
            }
        }

        if (!$allColumnsExist) {
            echo "  ⚠️  WARNING: Some columns are missing\n";
        }
    } else {
        echo "❌ FAIL: session_recordings table does NOT exist\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: Could not check database: " . $e->getMessage() . "\n";
}

// Summary
echo "\n\n=== TEST SUMMARY ===\n";
$allTestsPassed = $implementsInterface
    && $usesTrait
    && $allMethodsExist
    && $allRecordingMethodsExist
    && $allRoutesExist
    && $allControllerMethodsExist
    && $allWebhookRoutesExist
    && $allLiveKitMethodsExist;

if ($allTestsPassed) {
    echo "✅ ALL TESTS PASSED - Recording integration is complete!\n\n";
    echo "Next steps:\n";
    echo "1. Run finalize-recording-setup.sh on server (31.97.126.52)\n";
    echo "2. Test recording with actual Interactive Course session\n";
    echo "3. Verify webhook receives egress_ended event\n";
    echo "4. Check recording file appears in storage\n";
} else {
    echo "❌ SOME TESTS FAILED - Check errors above\n";
}

echo "\n";
