<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ParentProfile;
use App\Models\User;
use App\Models\Academy;

echo "=================================\n";
echo "Parent Email Validation Test\n";
echo "=================================\n\n";

// Get the first academy
$academy = Academy::first();
if (!$academy) {
    echo "❌ No academy found in database\n";
    exit(1);
}

echo "Testing with Academy: {$academy->name} (ID: {$academy->id})\n\n";

// Test email (new, never used)
$testEmail = 'brand-new-test-' . time() . '@example.com';
echo "Test email: {$testEmail}\n\n";

// Check 1: ParentProfile table
$parentProfileExists = ParentProfile::where('email', $testEmail)
    ->where('academy_id', $academy->id)
    ->exists();

echo "1. ParentProfile check (email + academy_id):\n";
echo "   Query: ParentProfile::where('email', '{$testEmail}')->where('academy_id', {$academy->id})->exists()\n";
echo "   Result: " . ($parentProfileExists ? '❌ EXISTS (should NOT exist!)' : '✅ NOT EXISTS (correct)') . "\n\n";

// Check 2: User table
$userExists = User::where('email', $testEmail)
    ->where('academy_id', $academy->id)
    ->exists();

echo "2. User check (email + academy_id):\n";
echo "   Query: User::where('email', '{$testEmail}')->where('academy_id', {$academy->id})->exists()\n";
echo "   Result: " . ($userExists ? '❌ EXISTS (should NOT exist!)' : '✅ NOT EXISTS (correct)') . "\n\n";

// Check 3: Would validation pass?
$validationWouldFail = $parentProfileExists || $userExists;

echo "3. Validation result:\n";
if ($validationWouldFail) {
    echo "   ❌ WOULD FAIL - Email appears to already exist\n";
    echo "   Error message: البريد الإلكتروني مسجل بالفعل في هذه الأكاديمية\n";
} else {
    echo "   ✅ WOULD PASS - Email is available\n";
}

echo "\n";

// Check 4: Check if email exists ANYWHERE (global check - wrong!)
$globalCheck = ParentProfile::where('email', $testEmail)->exists();
echo "4. Global check (WRONG - for comparison):\n";
echo "   Query: ParentProfile::where('email', '{$testEmail}')->exists()\n";
echo "   Result: " . ($globalCheck ? 'EXISTS' : 'NOT EXISTS') . "\n\n";

// Summary
echo "=================================\n";
echo "Summary:\n";
echo "=================================\n";
echo "Email: {$testEmail}\n";
echo "Academy: {$academy->name} (ID: {$academy->id})\n";
echo "ParentProfile exists (scoped): " . ($parentProfileExists ? 'YES' : 'NO') . "\n";
echo "User exists (scoped): " . ($userExists ? 'YES' : 'NO') . "\n";
echo "Registration should: " . ($validationWouldFail ? '❌ FAIL' : '✅ SUCCEED') . "\n";

echo "\n";
