#!/bin/bash

# 🔔 LiveKit Webhook Monitor
# Watches Laravel logs for webhook activity in real-time

echo ""
echo "🔔 LiveKit Webhook Monitor"
echo "=========================="
echo ""
echo "📡 Monitoring: storage/logs/laravel.log"
echo "🔍 Filtering: WEBHOOK, participant_joined, participant_left"
echo ""
echo "⏳ Waiting for webhook events... (Press Ctrl+C to stop)"
echo ""
echo "-----------------------------------------------------------"
echo ""

# Watch logs for webhook activity
tail -f storage/logs/laravel.log | grep --line-buffered -E "WEBHOOK|participant_joined|participant_left|Participant joined|Participant left" | while read line; do
    # Add timestamp
    echo "[$(date +'%H:%M:%S')] $line"
done
