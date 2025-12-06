#!/bin/bash
# Script to clean up orphaned parent records (profiles without users OR users without profiles)

echo "======================================"
echo "Parent Records Cleanup"
echo "======================================"
echo ""

echo "🔍 Checking for orphaned parent profiles..."
echo ""

php artisan tinker --execute="
// 1. Check orphaned parent profiles (profiles without users)
\$orphanedProfiles = \App\Models\ParentProfile::whereNull('user_id')
    ->where('created_at', '<', now()->subHours(24))
    ->get();

if (\$orphanedProfiles->isEmpty()) {
    echo '✓ No orphaned parent profiles found.' . PHP_EOL;
} else {
    echo '⚠️  Found ' . \$orphanedProfiles->count() . ' orphaned parent profile(s):' . PHP_EOL . PHP_EOL;

    foreach (\$orphanedProfiles as \$parent) {
        echo '  - ID: ' . \$parent->id . PHP_EOL;
        echo '    Email: ' . \$parent->email . PHP_EOL;
        echo '    Academy: ' . (\$parent->academy->name ?? 'N/A') . PHP_EOL;
        echo '    Created: ' . \$parent->created_at->diffForHumans() . PHP_EOL;
        echo '' . PHP_EOL;
    }

    echo '🗑️  Deleting orphaned parent profiles...' . PHP_EOL;
    \$deleted = \$orphanedProfiles->each(fn(\$p) => \$p->delete());
    echo '✓ Deleted ' . \$orphanedProfiles->count() . ' orphaned parent profile(s).' . PHP_EOL;
}

echo '' . PHP_EOL;
echo '🔍 Checking for orphaned parent users...' . PHP_EOL;
echo '' . PHP_EOL;

// 2. Check orphaned users (users without parent profiles)
\$orphanedUsers = \App\Models\User::where('user_type', 'parent')
    ->whereDoesntHave('parentProfile')
    ->where('created_at', '<', now()->subHours(24))
    ->get();

if (\$orphanedUsers->isEmpty()) {
    echo '✓ No orphaned parent users found.' . PHP_EOL;
} else {
    echo '⚠️  Found ' . \$orphanedUsers->count() . ' orphaned parent user(s):' . PHP_EOL . PHP_EOL;

    foreach (\$orphanedUsers as \$user) {
        echo '  - ID: ' . \$user->id . PHP_EOL;
        echo '    Email: ' . \$user->email . PHP_EOL;
        echo '    Academy: ' . (\$user->academy->name ?? 'N/A') . PHP_EOL;
        echo '    Created: ' . \$user->created_at->diffForHumans() . PHP_EOL;
        echo '' . PHP_EOL;
    }

    echo '🗑️  Deleting orphaned parent users...' . PHP_EOL;
    \$deleted = \$orphanedUsers->each(fn(\$u) => \$u->delete());
    echo '✓ Deleted ' . \$orphanedUsers->count() . ' orphaned parent user(s).' . PHP_EOL;
}
"

echo ""
echo "======================================"
echo "✅ Cleanup completed!"
echo "======================================"
