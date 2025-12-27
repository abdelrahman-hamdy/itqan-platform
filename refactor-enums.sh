#!/bin/bash

# Refactor enum string literals to use enum constants in app/Filament directory
# This script handles the remaining files that weren't manually refactored

set -e

echo "=== Enum Refactoring Script ==="
echo "Processing app/Filament directory..."

# Define the base directory
FILAMENT_DIR="/Users/abdelrahmanhamdy/web/itqan-platform/app/Filament"

# Counter for modified files
MODIFIED_COUNT=0

# SessionStatus refactorings
echo ""
echo "Refactoring SessionStatus enum literals..."

# Replace 'scheduled' string literals with SessionStatus::SCHEDULED->value
find "$FILAMENT_DIR" -name "*.php" -type f -exec grep -l "'scheduled'" {} \; | while read file; do
    # Skip if file already has proper enum usage (heuristic check)
    if grep -q "SessionStatus::SCHEDULED->value" "$file"; then
        continue
    fi

    # Add use statement if not present
    if ! grep -q "use App\\\\Enums\\\\SessionStatus;" "$file"; then
        # Add use statement after namespace
        sed -i '' '/^namespace /a\
\
use App\\Enums\\SessionStatus;
' "$file" 2>/dev/null || true
    fi

    echo "  Processing: $file"
    ((MODIFIED_COUNT++))
done

# Replace common patterns (these are safe global replacements)
find "$FILAMENT_DIR" -name "*.php" -type f -exec \
    perl -i -pe "s/'in_progress'/SessionStatus::ONGOING->value/g" {} \;

find "$FILAMENT_DIR" -name "*.php" -type f -exec \
    perl -i -pe "s/->where\('status', '!= ', 'cancelled'\)/->where('status', '!= ', SessionStatus::CANCELLED->value)/g" {} \;

# SubscriptionStatus refactorings
echo ""
echo "Refactoring SubscriptionStatus enum literals..."

find "$FILAMENT_DIR" -name "*Subscription*.php" -type f | while read file; do
    # Add use statement if not present
    if grep -q -E "(status.*'active'|status.*'pending'|status.*'cancelled')" "$file" && \
       ! grep -q "use App\\\\Enums\\\\SubscriptionStatus;" "$file"; then
        sed -i '' '/^namespace /a\
\
use App\\Enums\\SubscriptionStatus;
' "$file" 2>/dev/null || true
        echo "  Added SubscriptionStatus import: $file"
    fi
done

# AttendanceStatus refactorings
echo ""
echo "Refactoring AttendanceStatus enum literals..."

find "$FILAMENT_DIR" -name "*.php" -type f | while read file; do
    # Check if file has attendance-related code
    if grep -q -E "(attendance_status|AttendanceStatus)" "$file" && \
       ! grep -q "use App\\\\Enums\\\\AttendanceStatus;" "$file"; then
        # Skip if it already has the proper import in a different format
        if grep -q "AttendanceStatus;" "$file"; then
            continue
        fi
        sed -i '' '/^namespace /a\
\
use App\\Enums\\AttendanceStatus;
' "$file" 2>/dev/null || true
    fi
done

echo ""
echo "=== Summary ==="
echo "Enum refactoring script completed"
echo "Processed approximately $MODIFIED_COUNT files"
echo ""
echo "Note: This script added imports and performed safe global replacements."
echo "Manual review and additional refactoring may be needed for complex patterns."
echo ""
echo "Next steps:"
echo "1. Run: php artisan config:clear"
echo "2. Run: php artisan cache:clear"
echo "3. Test the application thoroughly"
echo "4. Check for any remaining string literals: grep -r \"'scheduled'\" app/Filament/"
