#!/bin/bash

# Dynamic Webhook Test - Uses actual room name from database
# Usage: ./test-dynamic-webhook.sh <session_id> <user_id>

SESSION_ID=${1:-121}
USER_ID=${2:-1}

echo "🧪 Dynamic Webhook Test"
echo "======================="
echo ""
echo "Session ID: $SESSION_ID"
echo "User ID: $USER_ID"
echo ""

# Get actual session data from database
SESSION_DATA=$(php artisan tinker --execute="
\$session = App\Models\QuranSession::with('academy')->find($SESSION_ID);
if (!\$session) {
    \$session = App\Models\AcademicSession::with('academy')->find($SESSION_ID);
}
if (!\$session) {
    echo 'ERROR:Session not found';
    exit(1);
}

\$user = App\Models\User::find($USER_ID);
if (!\$user) {
    echo 'ERROR:User not found';
    exit(1);
}

echo \$session->meeting_room_name . '|' . \$user->id . '_' . str_replace(' ', '_', \$user->first_name) . '_' . str_replace(' ', '_', \$user->last_name) . '|' . \$user->first_name . ' ' . \$user->last_name;
")

if [[ $SESSION_DATA == ERROR:* ]]; then
    echo "❌ ${SESSION_DATA#ERROR:}"
    exit 1
fi

# Parse session data
IFS='|' read -r ROOM_NAME IDENTITY USER_NAME <<< "$SESSION_DATA"

echo "Room Name: $ROOM_NAME"
echo "Identity: $IDENTITY"
echo "User Name: $USER_NAME"
echo ""

# Fixed participant SID for both events
PARTICIPANT_SID="PA_TEST_$(date +%s)"
JOIN_TIMESTAMP=$(date +%s)
LEAVE_TIMESTAMP=$((JOIN_TIMESTAMP + 180))  # 3 minutes later

echo "📍 Participant SID: $PARTICIPANT_SID"
echo "⏰ Simulating 3-minute session..."
echo ""

# Step 1: Send JOIN event
echo "1️⃣ Sending JOIN event..."
JOIN_RESPONSE=$(curl -s -k -X POST "https://itqan-platform.test/webhooks/livekit" \
  -H "Content-Type: application/webhook+json" \
  -H "User-Agent: Go-http-client/2.0" \
  -d "{
    \"event\": \"participant_joined\",
    \"id\": \"EV_JOIN_${JOIN_TIMESTAMP}\",
    \"createdAt\": ${JOIN_TIMESTAMP},
    \"room\": {
      \"name\": \"${ROOM_NAME}\",
      \"sid\": \"RM_TEST\",
      \"num_participants\": 1
    },
    \"participant\": {
      \"sid\": \"${PARTICIPANT_SID}\",
      \"identity\": \"${IDENTITY}\",
      \"name\": \"${USER_NAME}\",
      \"joinedAt\": ${JOIN_TIMESTAMP}
    }
  }")

if [ "$JOIN_RESPONSE" = "OK" ]; then
  echo "   ✅ JOIN event sent"
else
  echo "   ❌ Failed: $JOIN_RESPONSE"
  exit 1
fi

sleep 2

# Step 2: Send LEAVE event
echo "2️⃣ Sending LEAVE event..."
LEAVE_RESPONSE=$(curl -s -k -X POST "https://itqan-platform.test/webhooks/livekit" \
  -H "Content-Type: application/webhook+json" \
  -H "User-Agent: Go-http-client/2.0" \
  -d "{
    \"event\": \"participant_left\",
    \"id\": \"EV_LEAVE_${LEAVE_TIMESTAMP}\",
    \"createdAt\": ${LEAVE_TIMESTAMP},
    \"room\": {
      \"name\": \"${ROOM_NAME}\",
      \"sid\": \"RM_TEST\",
      \"num_participants\": 0
    },
    \"participant\": {
      \"sid\": \"${PARTICIPANT_SID}\",
      \"identity\": \"${IDENTITY}\",
      \"name\": \"${USER_NAME}\",
      \"joinedAt\": ${JOIN_TIMESTAMP}
    }
  }")

if [ "$LEAVE_RESPONSE" = "OK" ]; then
  echo "   ✅ LEAVE event sent"
else
  echo "   ❌ Failed: $LEAVE_RESPONSE"
  exit 1
fi

echo ""
echo "✅ Complete cycle sent! Expected duration: 3 minutes"
echo ""
echo "📊 Check results:"
echo "   php artisan attendance:debug $SESSION_ID"
echo ""
