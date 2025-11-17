#!/bin/bash

echo "🧪 Testing 2-Minute Session"
echo "============================"
echo ""

PARTICIPANT_SID="PA_REAL_TEST_456"
JOIN_TIMESTAMP=$(date +%s)
LEAVE_TIMESTAMP=$((JOIN_TIMESTAMP + 120))  # 2 minutes later

echo "⏰ Duration: 2 minutes"
echo ""

# JOIN
curl -s -k -X POST "https://itqan-platform.test/webhooks/livekit" \
  -H "Content-Type: application/webhook+json" \
  -H "User-Agent: Go-http-client/2.0" \
  -d "{
    \"event\": \"participant_joined\",
    \"id\": \"EV_JOIN_${JOIN_TIMESTAMP}\",
    \"createdAt\": ${JOIN_TIMESTAMP},
    \"room\": {
      \"name\": \"itqan-academy-quran-session-121\",
      \"sid\": \"RM_TEST\",
      \"num_participants\": 1
    },
    \"participant\": {
      \"sid\": \"${PARTICIPANT_SID}\",
      \"identity\": \"1_Ameer_Maher\",
      \"name\": \"Ameer Maher\",
      \"joinedAt\": ${JOIN_TIMESTAMP}
    }
  }" > /dev/null

echo "✅ JOIN sent"
sleep 1

# LEAVE
curl -s -k -X POST "https://itqan-platform.test/webhooks/livekit" \
  -H "Content-Type: application/webhook+json" \
  -H "User-Agent: Go-http-client/2.0" \
  -d "{
    \"event\": \"participant_left\",
    \"id\": \"EV_LEAVE_${LEAVE_TIMESTAMP}\",
    \"createdAt\": ${LEAVE_TIMESTAMP},
    \"room\": {
      \"name\": \"itqan-academy-quran-session-121\",
      \"sid\": \"RM_TEST\",
      \"num_participants\": 0
    },
    \"participant\": {
      \"sid\": \"${PARTICIPANT_SID}\",
      \"identity\": \"1_Ameer_Maher\",
      \"name\": \"Ameer Maher\",
      \"joinedAt\": ${JOIN_TIMESTAMP}
    }
  }" > /dev/null

echo "✅ LEAVE sent (expected duration: 2min)"
echo ""
