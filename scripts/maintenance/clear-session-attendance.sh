#!/bin/bash
# Clear attendance data for a specific session

SESSION_ID=${1:-121}

echo "🗑️  Clearing attendance data for session ${SESSION_ID}..."

php artisan tinker --execute="
\App\Models\MeetingAttendanceEvent::where('session_id', ${SESSION_ID})->delete();
\App\Models\MeetingAttendance::where('session_id', ${SESSION_ID})->delete();
echo '✅ Cleared attendance data for session ${SESSION_ID}' . PHP_EOL;
"

echo ""
echo "Ready to test! Join session ${SESSION_ID} now."
