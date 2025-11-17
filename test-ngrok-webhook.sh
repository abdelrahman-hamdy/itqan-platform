#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║        Test ngrok Webhook Connection                      ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Get ngrok URL from user
echo "Enter your ngrok URL (e.g., https://abc123.ngrok.io):"
read -r NGROK_URL

if [ -z "$NGROK_URL" ]; then
    echo "❌ ngrok URL is required"
    exit 1
fi

# Remove trailing slash
NGROK_URL="${NGROK_URL%/}"

echo ""
echo "Testing webhook endpoint: ${NGROK_URL}/webhooks/livekit"
echo ""

# Test 1: Basic connectivity
echo "🧪 Test 1: Basic connectivity..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${NGROK_URL}/webhooks/livekit" \
    -H "Content-Type: application/json" \
    -d '{"event":"test"}' 2>&1)

if [ "$RESPONSE" = "200" ]; then
    echo "   ✅ Webhook endpoint is reachable!"
else
    echo "   ❌ Failed to reach webhook endpoint (HTTP $RESPONSE)"
    echo "   Check that:"
    echo "      - ngrok tunnel is running"
    echo "      - Laravel server is running (php artisan serve)"
    echo "      - ngrok URL is correct"
    exit 1
fi

echo ""
echo "🧪 Test 2: Simulating participant_joined webhook..."

# Simulate a real participant_joined webhook
WEBHOOK_DATA=$(cat <<EOF
{
    "event": "participant_joined",
    "id": "EV_TEST_$(date +%s)",
    "createdAt": $(date +%s),
    "room": {
        "name": "session-121-quran",
        "sid": "RM_TEST",
        "num_participants": 1
    },
    "participant": {
        "sid": "PA_TEST_$(date +%s)",
        "identity": "itqan_1",
        "name": "Test User",
        "joinedAt": $(date +%s)
    }
}
EOF
)

RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${NGROK_URL}/webhooks/livekit" \
    -H "Content-Type: application/json" \
    -d "$WEBHOOK_DATA")

if [ "$RESPONSE" = "200" ]; then
    echo "   ✅ Webhook processed successfully!"
else
    echo "   ❌ Webhook failed (HTTP $RESPONSE)"
fi

echo ""
echo "🔍 Check Laravel logs for webhook processing:"
echo "   tail -10 storage/logs/laravel.log | grep 'WEBHOOK'"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Tests complete!"
echo ""
echo "Next steps:"
echo "1. Add this URL to LiveKit dashboard:"
echo "   ${NGROK_URL}/webhooks/livekit"
echo ""
echo "2. Enable events: participant_joined, participant_left"
echo ""
echo "3. Join a meeting and watch:"
echo "   php artisan attendance:debug --watch"
echo ""

