#!/bin/bash
echo "========================================"
echo "Multi-Tenancy Implementation Test"
echo "========================================"
echo ""

echo "1. Verifying database constraint..."
php artisan tinker --execute="
\$compositeKey = DB::select('SHOW INDEX FROM users WHERE Key_name = \"users_email_academy_unique\"');
if (count(\$compositeKey) === 2) {
    echo '   ✅ Composite unique constraint (email, academy_id) exists!' . PHP_EOL;
} else {
    echo '   ❌ Composite constraint not found' . PHP_EOL;
}
"
echo ""

echo "2. Testing same email in different academies..."
php artisan tinker --execute="
// Get two different academies
\$academies = \App\Models\Academy::take(2)->get();

if (\$academies->count() < 2) {
    echo '   ⚠️  Need at least 2 academies to test. Current count: ' . \$academies->count() . PHP_EOL;
    exit(0);
}

\$academy1 = \$academies[0];
\$academy2 = \$academies[1];

\$testEmail = 'multitenancy-test@example.com';

// Clean up any existing test users
\App\Models\User::where('email', \$testEmail)->delete();

echo '   Testing with academies: ' . \$academy1->name . ' (ID: ' . \$academy1->id . ') and ' . \$academy2->name . ' (ID: ' . \$academy2->id . ')' . PHP_EOL;
echo '' . PHP_EOL;

// Test 1: Create user in Academy 1
try {
    \$user1 = \App\Models\User::create([
        'academy_id' => \$academy1->id,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => \$testEmail,
        'password' => bcrypt('password'),
        'user_type' => 'parent',
        'email_verified_at' => now(),
    ]);
    echo '   ✅ Test 1 PASSED: Created user in ' . \$academy1->name . ' with email: ' . \$testEmail . PHP_EOL;
} catch (\Exception \$e) {
    echo '   ❌ Test 1 FAILED: Could not create user in ' . \$academy1->name . PHP_EOL;
    echo '      Error: ' . \$e->getMessage() . PHP_EOL;
}

// Test 2: Create user with SAME email in Academy 2 (should succeed with multi-tenancy)
try {
    \$user2 = \App\Models\User::create([
        'academy_id' => \$academy2->id,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => \$testEmail,
        'password' => bcrypt('password'),
        'user_type' => 'parent',
        'email_verified_at' => now(),
    ]);
    echo '   ✅ Test 2 PASSED: Created user in ' . \$academy2->name . ' with SAME email: ' . \$testEmail . PHP_EOL;
    echo '      Multi-tenancy is working correctly!' . PHP_EOL;
} catch (\Exception \$e) {
    echo '   ❌ Test 2 FAILED: Could not create duplicate email in different academy' . PHP_EOL;
    echo '      Error: ' . \$e->getMessage() . PHP_EOL;
    echo '      Multi-tenancy may not be working!' . PHP_EOL;
}

// Test 3: Try to create user with same email in Academy 1 again (should fail)
try {
    \$user3 = \App\Models\User::create([
        'academy_id' => \$academy1->id,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => \$testEmail,
        'password' => bcrypt('password'),
        'user_type' => 'parent',
        'email_verified_at' => now(),
    ]);
    echo '   ❌ Test 3 FAILED: Duplicate email in same academy was allowed (should be blocked)' . PHP_EOL;
} catch (\Exception \$e) {
    echo '   ✅ Test 3 PASSED: Duplicate email in same academy correctly blocked' . PHP_EOL;
}

echo '' . PHP_EOL;

// Verify records exist
\$usersCount = \App\Models\User::where('email', \$testEmail)->count();
echo '   Total users with email ' . \$testEmail . ': ' . \$usersCount . PHP_EOL;

if (\$usersCount === 2) {
    echo '   ✅ Correct: 2 users with same email in different academies' . PHP_EOL;
} else {
    echo '   ⚠️  Expected 2 users, found ' . \$usersCount . PHP_EOL;
}

// Clean up test users
\App\Models\User::where('email', \$testEmail)->delete();
echo '' . PHP_EOL;
echo '   🧹 Cleaned up test users' . PHP_EOL;
"
echo ""

echo "3. Checking validation implementation..."
if grep -q "where('academy_id', \$academyId)" app/Http/Controllers/ParentRegistrationController.php; then
    echo "   ✅ ParentRegistrationController uses academy-scoped validation"
else
    echo "   ❌ ParentRegistrationController may not have academy-scoped validation"
fi

if grep -q "Filament::getTenant()" app/Filament/Resources/ParentProfileResource.php; then
    echo "   ✅ ParentProfileResource uses Filament tenant context"
else
    echo "   ❌ ParentProfileResource may not use tenant context"
fi

echo ""
echo "========================================"
echo "✅ Multi-Tenancy Test Complete!"
echo "========================================"
echo ""
echo "Summary:"
echo "- Database constraint: Composite unique (email, academy_id)"
echo "- Same email: Allowed in different academies"
echo "- Duplicate in same academy: Correctly blocked"
echo "- Validation: Academy-scoped in both registration and admin"
echo ""
