#!/bin/bash

echo "🧪 Testing LiveKit LEAVE Event"
echo "This simulates a participant leaving the session..."
echo ""

# Simulate a participant_left webhook
TIMESTAMP=$(date +%s)
EVENT_ID="EV_LEAVE_${TIMESTAMP}"

curl -s -k -X POST "https://itqan-platform.test/webhooks/livekit" \
  -H "Content-Type: application/webhook+json" \
  -H "User-Agent: Go-http-client/2.0" \
  -d "{
    \"event\": \"participant_left\",
    \"id\": \"${EVENT_ID}\",
    \"createdAt\": ${TIMESTAMP},
    \"room\": {
      \"name\": \"itqan-academy-quran-session-121\",
      \"sid\": \"RM_TEST\",
      \"num_participants\": 0
    },
    \"participant\": {
      \"sid\": \"PA_TEST_1763\",
      \"identity\": \"1_Ameer_Maher\",
      \"name\": \"Ameer Maher\",
      \"joinedAt\": $(date -j -v-5M +%s)
    }
  }"

echo ""
echo ""
echo "✅ LEAVE event sent! Check results:"
echo "   php artisan attendance:debug 121"
echo ""
