#!/bin/bash

echo "🧪 Testing LiveKit Webhook Directly (Bypassing ngrok)"
echo "This simulates what LiveKit should send..."
echo ""

# Simulate a participant_joined webhook
TIMESTAMP=$(date +%s)
EVENT_ID="EV_TEST_${TIMESTAMP}"

curl -s -k -X POST "https://itqan-platform.test/webhooks/livekit" \
  -H "Content-Type: application/webhook+json" \
  -H "User-Agent: Go-http-client/2.0" \
  -d "{
    \"event\": \"participant_joined\",
    \"id\": \"${EVENT_ID}\",
    \"createdAt\": ${TIMESTAMP},
    \"room\": {
      \"name\": \"itqan-academy-quran-session-121\",
      \"sid\": \"RM_TEST\",
      \"num_participants\": 1
    },
    \"participant\": {
      \"sid\": \"PA_TEST_${TIMESTAMP}\",
      \"identity\": \"1_Ameer_Maher\",
      \"name\": \"Ameer Maher\",
      \"joinedAt\": ${TIMESTAMP}
    }
  }"

echo ""
echo ""
echo "✅ Webhook sent! Check results:"
echo "   php artisan attendance:debug 121"
echo ""
