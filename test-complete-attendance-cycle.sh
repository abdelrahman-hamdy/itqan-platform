#!/bin/bash

echo "🧪 Testing Complete Attendance Cycle (JOIN → LEAVE)"
echo "======================================================"
echo ""

# Fixed participant SID for both events
PARTICIPANT_SID="PA_TEST_FIXED_123"
JOIN_TIMESTAMP=$(date +%s)
LEAVE_TIMESTAMP=$((JOIN_TIMESTAMP + 300))  # 5 minutes later

echo "📍 Participant SID: $PARTICIPANT_SID"
echo "⏰ Join time: $(date -r $JOIN_TIMESTAMP '+%H:%M:%S')"
echo "⏰ Leave time: $(date -r $LEAVE_TIMESTAMP '+%H:%M:%S') (5 minutes later)"
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
  }")

if [ "$JOIN_RESPONSE" = "OK" ]; then
  echo "   ✅ JOIN event sent successfully"
else
  echo "   ❌ JOIN event failed: $JOIN_RESPONSE"
  exit 1
fi

# Wait a moment for processing
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
  }")

if [ "$LEAVE_RESPONSE" = "OK" ]; then
  echo "   ✅ LEAVE event sent successfully"
else
  echo "   ❌ LEAVE event failed: $LEAVE_RESPONSE"
  exit 1
fi

echo ""
echo "✅ Complete cycle sent! Expected duration: ~5 minutes"
echo ""
echo "📊 Check results with:"
echo "   php artisan attendance:debug 121"
echo ""
