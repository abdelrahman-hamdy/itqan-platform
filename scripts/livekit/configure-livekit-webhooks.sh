#!/bin/bash

# LiveKit Webhook Configuration Script
# This script helps configure the self-hosted LiveKit server to send webhooks to your Laravel app

NGROK_URL="https://percolative-unyielded-taneka.ngrok-free.dev"
WEBHOOK_URL="${NGROK_URL}/webhooks/livekit"
LIVEKIT_SERVER="31.97.126.52"

echo "========================================="
echo "LiveKit Webhook Configuration"
echo "========================================="
echo ""
echo "✅ ngrok tunnel is running at: $NGROK_URL"
echo "✅ Webhook endpoint: $WEBHOOK_URL"
echo ""
echo "🎯 Testing webhook endpoint..."
HEALTH_CHECK=$(curl -s $NGROK_URL/webhooks/livekit/health)
if echo "$HEALTH_CHECK" | grep -q "ok"; then
    echo "✅ Webhook endpoint is accessible!"
    echo "   Response: $HEALTH_CHECK"
else
    echo "❌ Webhook endpoint is NOT accessible!"
    echo "   Response: $HEALTH_CHECK"
    echo ""
    echo "   Make sure ngrok is running with --host-header=rewrite:"
    echo "   ngrok http https://itqan-platform.test:443 --host-header=rewrite"
    exit 1
fi
echo ""
echo "📋 You need to SSH to the LiveKit server and configure webhooks:"
echo ""
echo "Step 1: SSH to LiveKit server"
echo "---------------------------------------"
echo "ssh root@$LIVEKIT_SERVER"
echo ""
echo "Step 2: Edit LiveKit configuration"
echo "---------------------------------------"
echo "nano /opt/livekit/livekit.yaml"
echo ""
echo "Step 3: Add webhook configuration (copy this exactly)"
echo "---------------------------------------"
cat << 'EOF'
webhook:
  urls:
    - https://percolative-unyielded-taneka.ngrok-free.dev/webhooks/livekit
  api_key: APIxdLnkvjeS3PV
EOF
echo ""
echo "Step 4: Save and exit (Ctrl+X, then Y, then Enter)"
echo ""
echo "Step 5: Restart LiveKit"
echo "---------------------------------------"
echo "cd /opt/livekit && docker-compose restart"
echo ""
echo "Step 6: Verify LiveKit is running"
echo "---------------------------------------"
echo "docker ps | grep livekit"
echo ""
echo "========================================="
echo "After configuration, test the webhook:"
echo "========================================="
echo ""
echo "From your local machine, run:"
echo "curl $WEBHOOK_URL/health"
echo ""
echo "Expected response: {\"status\":\"ok\",\"timestamp\":\"...\",\"service\":\"livekit-webhooks\"}"
echo ""
