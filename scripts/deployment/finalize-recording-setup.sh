#!/bin/bash

# Finalize LiveKit Recording Setup Script
# This script completes the recording feature configuration

set -e

echo "=== LiveKit Recording Feature - Final Setup ==="
echo

# Step 1: Extract API credentials from livekit.yaml
echo "Step 1: Extracting API credentials from livekit.yaml..."
cd /opt/livekit/conference.itqanway.com

API_KEY=$(grep -A 1 "keys:" livekit.yaml | tail -n 1 | awk '{print $1}' | tr -d ':')
API_SECRET=$(grep -A 1 "keys:" livekit.yaml | tail -n 1 | awk '{print $2}')

echo "API Key: $API_KEY"
echo "API Secret: ${API_SECRET:0:20}..."
echo

# Step 2: Update egress.yaml with correct credentials
echo "Step 2: Updating egress.yaml with correct API credentials..."

cat > egress.yaml <<EOF
# LiveKit Egress Configuration
# Purpose: Recording interactive course sessions

# API Credentials (must match livekit.yaml)
api_key: $API_KEY
api_secret: $API_SECRET

# LiveKit Server URL (localhost via host network)
ws_url: ws://127.0.0.1:7880

# Redis (localhost via host network)
redis:
  address: 127.0.0.1:6379

# Local file storage
file_output:
  local:
    enabled: true
    output_directory: /recordings

# Logging
log_level: info

# Health check
health_port: 9090

# Performance tuning
cpu_cost:
  room_composite_cpu_cost: 3.0
EOF

echo "✅ egress.yaml updated"
echo

# Step 3: Add webhook URL to livekit.yaml
echo "Step 3: Configuring webhook URL in livekit.yaml..."

# Check if webhook section already exists
if grep -q "webhook:" livekit.yaml; then
    echo "⚠️  Webhook section already exists in livekit.yaml"
else
    # Add webhook section before the last line
    cat >> livekit.yaml <<EOF

# Webhook configuration for recording events
webhook:
  api_key: $API_KEY
  urls:
    - https://itqan-platform.test/webhooks/livekit
EOF
    echo "✅ Webhook configuration added to livekit.yaml"
fi
echo

# Step 4: Restart services
echo "Step 4: Restarting services to apply changes..."
docker compose restart livekit egress

echo "Waiting for services to restart..."
sleep 10
echo

# Step 5: Verify services
echo "Step 5: Verifying services..."
echo
echo "Container Status:"
docker compose ps

echo
echo "Egress Logs (last 20 lines):"
docker logs livekit-egress --tail 20

echo
echo "LiveKit Logs (last 20 lines):"
docker logs livekit-server --tail 20 | grep -i "webhook\|egress" || echo "No webhook/egress related logs yet"

echo
echo "=== Setup Complete! ==="
echo
echo "Next steps:"
echo "1. Test webhook endpoint: curl https://itqan-platform.test/webhooks/livekit/health"
echo "2. Create a test recording from an Interactive Course session"
echo "3. Check recordings directory: ls -lh /opt/livekit/conference.itqanway.com/recordings/"
echo
