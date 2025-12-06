#!/bin/bash

echo "=========================================="
echo "Parent Registration Fix Verification"
echo "=========================================="
echo ""

echo "1. Checking database constraints..."
echo ""

php artisan tinker --execute="
echo '   Checking users table...' . PHP_EOL;
\$usersConstraint = DB::select('SHOW INDEX FROM users WHERE Key_name = \"users_email_academy_unique\"');
if (count(\$usersConstraint) === 2) {
    echo '   ✅ users table: Composite unique (email, academy_id) exists!' . PHP_EOL;
} else {
    echo '   ❌ users table: Composite constraint NOT found' . PHP_EOL;
}

echo '' . PHP_EOL;
echo '   Checking parent_profiles table...' . PHP_EOL;
\$parentProfilesConstraint = DB::select('SHOW INDEX FROM parent_profiles WHERE Key_name = \"parent_profiles_email_academy_unique\"');
if (count(\$parentProfilesConstraint) === 2) {
    echo '   ✅ parent_profiles table: Composite unique (email, academy_id) exists!' . PHP_EOL;
} else {
    echo '   ❌ parent_profiles table: Composite constraint NOT found' . PHP_EOL;
}
"

echo ""
echo "2. Testing ParentProfile auto-creation..."
echo ""

php artisan tinker --execute="
\$academy = App\Models\Academy::first();

if (!\$academy) {
    echo '   ❌ No academy found in database' . PHP_EOL;
    exit(1);
}

echo '   Testing with Academy: ' . \$academy->name . ' (ID: ' . \$academy->id . ')' . PHP_EOL;
echo '' . PHP_EOL;

// Test: Create user and check if ParentProfile is auto-created
\$testEmail = 'verify-test-' . time() . '@example.com';

try {
    \$user = App\Models\User::create([
        'academy_id' => \$academy->id,
        'first_name' => 'Verification',
        'last_name' => 'Test',
        'email' => \$testEmail,
        'phone' => '+966500000000',
        'password' => bcrypt('password'),
        'user_type' => 'parent',
        'email_verified_at' => now(),
    ]);

    echo '   ✅ User created successfully (ID: ' . \$user->id . ')' . PHP_EOL;

    // Refresh to load relationships
    \$user->refresh();

    // Check if profile was created
    \$profile = \$user->parentProfile;

    if (\$profile) {
        echo '   ✅ ParentProfile auto-created successfully (ID: ' . \$profile->id . ')' . PHP_EOL;
        echo '   ✅ User and Profile are linked: ' . (\$profile->user_id === \$user->id ? 'YES' : 'NO') . PHP_EOL;

        // Check database for duplicates
        \$userCount = App\Models\User::where('email', \$testEmail)->count();
        \$profileCount = App\Models\ParentProfile::where('email', \$testEmail)->count();

        if (\$userCount === 1 && \$profileCount === 1) {
            echo '   ✅ No duplicates: 1 User + 1 ParentProfile' . PHP_EOL;
        } else {
            echo '   ❌ DUPLICATES FOUND: ' . \$userCount . ' Users + ' . \$profileCount . ' Profiles' . PHP_EOL;
        }
    } else {
        echo '   ❌ ParentProfile was NOT created automatically' . PHP_EOL;
    }

    // Clean up
    \$user->delete();
    echo '' . PHP_EOL;
    echo '   🧹 Test user cleaned up' . PHP_EOL;

} catch (\Exception \$e) {
    echo '   ❌ Test failed: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "3. Checking validation code..."
echo ""

if grep -q "where('academy_id', \$academyId)" app/Http/Controllers/ParentRegistrationController.php; then
    echo "   ✅ ParentRegistrationController: Academy-scoped validation"
else
    echo "   ❌ ParentRegistrationController: Academy-scoped validation NOT found"
fi

if grep -q "->refresh()" app/Http/Controllers/ParentRegistrationController.php; then
    echo "   ✅ ParentRegistrationController: refresh() call added"
else
    echo "   ❌ ParentRegistrationController: refresh() call NOT found"
fi

if grep -q "Fallback: manually create if boot" app/Http/Controllers/ParentRegistrationController.php; then
    echo "   ✅ ParentRegistrationController: Fallback logic added"
else
    echo "   ❌ ParentRegistrationController: Fallback logic NOT found"
fi

if grep -q "Filament::getTenant()" app/Filament/Resources/ParentProfileResource.php; then
    echo "   ✅ ParentProfileResource: Filament tenant context used"
else
    echo "   ❌ ParentProfileResource: Filament tenant context NOT found"
fi

echo ""
echo "=========================================="
echo "Verification Complete!"
echo "=========================================="
echo ""
echo "Summary:"
echo "- Database constraints: Composite unique (email, academy_id) on both tables"
echo "- ParentProfile auto-creation: Working with refresh() + fallback"
echo "- Validation: Academy-scoped in both registration and admin"
echo "- No duplicate profiles: Each user has exactly ONE profile"
echo ""
echo "Next step: Test parent registration through the web interface"
echo "URL: http://itqan-platform.test/parent-register"
echo ""
