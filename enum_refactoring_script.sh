#!/bin/bash
# Enum Refactoring Script for Laravel Models
# This script replaces all string-based enum literals with proper enum constants

# Function to add import if not present
add_import_if_missing() {
    local file=$1
    local import=$2

    # Check if import already exists
    if ! grep -q "$import" "$file"; then
        # Add import after the namespace line
        sed -i.bak "/^namespace /a\\
$import
" "$file"
    fi
}

# Function to refactor attendance status literals
refactor_attendance_status() {
    local file=$1

    # Add import
    add_import_if_missing "$file" "use App\\Enums\\AttendanceStatus;"

    # Replace string literals with enum constants (non-comparison contexts)
    sed -i.bak "s/'attendance_status' => 'attended'/attendance_status' => AttendanceStatus::ATTENDED->value/g" "$file"
    sed -i.bak "s/'attendance_status' => 'late'/'attendance_status' => AttendanceStatus::LATE->value/g" "$file"
    sed -i.bak "s/'attendance_status' => 'absent'/'attendance_status' => AttendanceStatus::ABSENT->value/g" "$file"
    sed -i.bak "s/'attendance_status' => 'leaved'/'attendance_status' => AttendanceStatus::LEAVED->value/g" "$file"

    # Replace in comparisons
    sed -i.bak "s/=== 'attended'/=== AttendanceStatus::ATTENDED->value/g" "$file"
    sed -i.bak "s/=== 'late'/=== AttendanceStatus::LATE->value/g" "$file"
    sed -i.bak "s/=== 'absent'/=== AttendanceStatus::ABSENT->value/g" "$file"
    sed -i.bak "s/=== 'leaved'/=== AttendanceStatus::LEAVED->value/g" "$file"

    sed -i.bak "s/!== 'attended'/!== AttendanceStatus::ATTENDED->value/g" "$file"
    sed -i.bak "s/!== 'late'/!== AttendanceStatus::LATE->value/g" "$file"
    sed -i.bak "s/!== 'absent'/!== AttendanceStatus::ABSENT->value/g" "$file"
    sed -i.bak "s/!== 'leaved'/!== AttendanceStatus::LEAVED->value/g" "$file"

    # Replace returns
    sed -i.bak "s/return 'attended'/return AttendanceStatus::ATTENDED->value/g" "$file"
    sed -i.bak "s/return 'late'/return AttendanceStatus::LATE->value/g" "$file"
    sed -i.bak "s/return 'absent'/return AttendanceStatus::ABSENT->value/g" "$file"
    sed -i.bak "s/return 'leaved'/return AttendanceStatus::LEAVED->value/g" "$file"

    # Replace in ternary operators
    sed -i.bak "s/? 'attended'/? AttendanceStatus::ATTENDED->value/g" "$file"
    sed -i.bak "s/? 'late'/? AttendanceStatus::LATE->value/g" "$file"
    sed -i.bak "s/? 'absent'/? AttendanceStatus::ABSENT->value/g" "$file"
    sed -i.bak "s/? 'leaved'/? AttendanceStatus::LEAVED->value/g" "$file"

    sed -i.bak "s/: 'attended'/: AttendanceStatus::ATTENDED->value/g" "$file"
    sed -i.bak "s/: 'late'/: AttendanceStatus::LATE->value/g" "$file"
    sed -i.bak "s/: 'absent'/: AttendanceStatus::ABSENT->value/g" "$file"
    sed -i.bak "s/: 'leaved'/: AttendanceStatus::LEAVED->value/g" "$file"
}

# Function to refactor session status literals
refactor_session_status() {
    local file=$1

    # Add import
    add_import_if_missing "$file" "use App\\Enums\\SessionStatus;"

    # Replace string literals in comparisons and assignments
    for status in "scheduled" "ongoing" "completed" "cancelled" "ready" "absent"; do
        UPPER=$(echo "$status" | tr '[:lower:]' '[:upper:]')

        # Comparisons
        sed -i.bak "s/=== '$status'/=== SessionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/!== '$status'/!== SessionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/== '$status'/== SessionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/!= '$status'/!= SessionStatus::${UPPER}->value/g" "$file"

        # Assignments (be careful with these)
        sed -i.bak "s/'status' => '$status'/'status' => SessionStatus::${UPPER}->value/g" "$file"

        # Returns
        sed -i.bak "s/return '$status'/return SessionStatus::${UPPER}->value/g" "$file"

        # Ternary operators
        sed -i.bak "s/? '$status'/? SessionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/: '$status'/: SessionStatus::${UPPER}->value/g" "$file"
    done
}

# Function to refactor subscription status literals
refactor_subscription_status() {
    local file=$1

    # Add import
    add_import_if_missing "$file" "use App\\Enums\\SubscriptionStatus;"

    # Replace string literals
    for status in "pending" "active" "paused" "expired" "cancelled" "completed" "refunded"; do
        UPPER=$(echo "$status" | tr '[:lower:]' '[:upper:]')

        # Comparisons
        sed -i.bak "s/=== '$status'/=== SubscriptionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/!== '$status'/!== SubscriptionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/== '$status'/== SubscriptionStatus::${UPPER}->value/g" "$file"
        sed -i.bak "s/!= '$status'/!= SubscriptionStatus::${UPPER}->value/g" "$file"

        # Assignments
        sed -i.bak "s/'status' => '$status'/'status' => SubscriptionStatus::${UPPER}->value/g" "$file"

        # Returns
        sed -i.bak "s/return '$status'/return SubscriptionStatus::${UPPER}->value/g" "$file"
    done
}

# Main execution
echo "Starting enum refactoring..."

# List of files to refactor
MODEL_FILES=(
    "app/Models/BaseSession.php"
    "app/Models/BaseSessionAttendance.php"
    "app/Models/BaseSessionReport.php"
    "app/Models/AcademicSession.php"
    "app/Models/AcademicSessionReport.php"
    "app/Models/QuranSession.php"
    "app/Models/InteractiveCourseSession.php"
    "app/Models/MeetingAttendance.php"
    "app/Models/QuranSubscription.php"
    "app/Models/AcademicSubscription.php"
    "app/Models/BaseSubscription.php"
    "app/Models/CourseSubscription.php"
    "app/Models/QuranCircle.php"
    "app/Models/QuranIndividualCircle.php"
    "app/Models/AcademicIndividualLesson.php"
    "app/Models/SessionRequest.php"
    "app/Models/HomeworkSubmission.php"
    "app/Models/InteractiveCourseEnrollment.php"
    "app/Models/Payment.php"
)

for file in "${MODEL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "Refactoring $file..."
        refactor_attendance_status "$file"
        refactor_session_status "$file"
        refactor_subscription_status "$file"
        # Remove backup files
        rm -f "$file.bak"
    else
        echo "Warning: $file not found"
    fi
done

echo "Enum refactoring complete!"
