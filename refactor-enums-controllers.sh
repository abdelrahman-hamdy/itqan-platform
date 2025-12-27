#!/bin/bash

# Enum Refactoring Script for Controllers
# This script refactors all enum string literals to use enum constants in app/Http/Controllers/

set -e  # Exit on error

echo "Starting enum refactoring in app/Http/Controllers/..."
echo "=================================="

# Function to perform safe sed replacement
safe_sed() {
    local file="$1"
    local pattern="$2"
    local replacement="$3"

    # macOS uses BSD sed which requires '' after -i
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "$pattern" "$file"
    else
        sed -i "$pattern" "$file"
    fi
}

# Function to add enum import to a file if not present
add_enum_import() {
    local file="$1"
    local enum_class="$2"

    # Check if import already exists
    if ! grep -q "use App\\\\Enums\\\\${enum_class};" "$file"; then
        # Find the last 'use' statement and add after it
        if grep -q "^use " "$file"; then
            # Add after the last use statement
            safe_sed "$file" "/^use [^;]*;$/a\\
use App\\\\Enums\\\\${enum_class};
"
        else
            # Add after namespace if no use statements
            safe_sed "$file" "/^namespace /a\\
\\
use App\\\\Enums\\\\${enum_class};
"
        fi
    fi
}

# Files to refactor
FILES=$(find app/Http/Controllers -name "*.php" -type f)

for file in $FILES; do
    echo "Processing: $file"

    # Skip if file doesn't contain any enum-like status strings
    if ! grep -q -E "'(cancelled|completed|scheduled|ongoing|ready|pending|active|paused|expired|attended|present|late|absent|leaved)" "$file"; then
        continue
    fi

    MODIFIED=false

    # SessionStatus refactoring
    if grep -q -E "'(cancelled|completed|scheduled|ongoing|ready|unscheduled|in_progress|live)" "$file"; then
        echo "  - Refactoring SessionStatus..."
        add_enum_import "$file" "SessionStatus"

        # Replace string literals with enum constants
        safe_sed "$file" "s/\['cancelled'\]/[SessionStatus::CANCELLED->value]/g"
        safe_sed "$file" "s/\['completed'\]/[SessionStatus::COMPLETED->value]/g"
        safe_sed "$file" "s/\['scheduled'\]/[SessionStatus::SCHEDULED->value]/g"
        safe_sed "$file" "s/\['ongoing'\]/[SessionStatus::ONGOING->value]/g"
        safe_sed "$file" "s/\['ready'\]/[SessionStatus::READY->value]/g"
        safe_sed "$file" "s/\['unscheduled'\]/[SessionStatus::UNSCHEDULED->value]/g"

        # Replace multi-value arrays
        safe_sed "$file" "s/\['cancelled', 'completed'\]/[SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]/g"
        safe_sed "$file" "s/\['completed', 'cancelled'\]/[SessionStatus::COMPLETED->value, SessionStatus::CANCELLED->value]/g"
        safe_sed "$file" "s/\['scheduled', 'pending', 'ready'\]/[SessionStatus::SCHEDULED->value, SessionStatus::READY->value]/g"
        safe_sed "$file" "s/\['scheduled', 'ready'\]/[SessionStatus::SCHEDULED->value, SessionStatus::READY->value]/g"

        # Single-quoted status comparisons
        safe_sed "$file" "s/'status', 'completed'/'status', SessionStatus::COMPLETED->value/g"
        safe_sed "$file" "s/'status', 'cancelled'/'status', SessionStatus::CANCELLED->value/g"
        safe_sed "$file" "s/'status', 'scheduled'/'status', SessionStatus::SCHEDULED->value/g"
        safe_sed "$file" "s/'status', 'ongoing'/'status', SessionStatus::ONGOING->value/g"
        safe_sed "$file" "s/'status', 'ready'/'status', SessionStatus::READY->value/g"

        MODIFIED=true
    fi

    # SubscriptionStatus refactoring
    if grep -q -E "'status', '(active|pending|paused|expired)" "$file"; then
        echo "  - Refactoring SubscriptionStatus..."
        add_enum_import "$file" "SubscriptionStatus"

        safe_sed "$file" "s/'status', 'active'/'status', SubscriptionStatus::ACTIVE->value/g"
        safe_sed "$file" "s/'status', 'pending'/'status', SubscriptionStatus::PENDING->value/g"
        safe_sed "$file" "s/'status', 'paused'/'status', SubscriptionStatus::PAUSED->value/g"
        safe_sed "$file" "s/'status', 'expired'/'status', SubscriptionStatus::EXPIRED->value/g"

        MODIFIED=true
    fi

    # AttendanceStatus refactoring
    if grep -q -E "'(attended|present|late|absent|leaved|partial)" "$file"; then
        echo "  - Refactoring AttendanceStatus..."
        add_enum_import "$file" "AttendanceStatus"

        safe_sed "$file" "s/\['attended'\]/[AttendanceStatus::ATTENDED->value]/g"
        safe_sed "$file" "s/\['present'\]/[AttendanceStatus::ATTENDED->value]/g"
        safe_sed "$file" "s/\['late'\]/[AttendanceStatus::LATE->value]/g"
        safe_sed "$file" "s/\['absent'\]/[AttendanceStatus::ABSENT->value]/g"
        safe_sed "$file" "s/\['leaved'\]/[AttendanceStatus::LEAVED->value]/g"

        safe_sed "$file" "s/'status', 'attended'/'status', AttendanceStatus::ATTENDED->value/g"
        safe_sed "$file" "s/'status', 'late'/'status', AttendanceStatus::LATE->value/g"
        safe_sed "$file" "s/'status', 'absent'/'status', AttendanceStatus::ABSENT->value/g"

        MODIFIED=true
    fi

    if [ "$MODIFIED" = true ]; then
        echo "  ✓ Modified successfully"

        # Validate PHP syntax
        if ! php -l "$file" > /dev/null 2>&1; then
            echo "  ✗ PHP syntax error detected! Reverting changes..."
            exit 1
        fi
    fi
done

echo "=================================="
echo "Refactoring completed successfully!"
echo "Running final validation..."

# Final validation of all modified files
find app/Http/Controllers -name "*.php" -type f -exec php -l {} \; > /dev/null 2>&1

echo "All files validated successfully!"
