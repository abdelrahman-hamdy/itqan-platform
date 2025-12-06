#!/bin/bash

# Webhook Monitoring Script
# This script helps you monitor incoming webhooks from LiveKit server in real-time

echo "========================================="
echo "LiveKit Webhook Monitor"
echo "========================================="
echo ""
echo "Monitoring webhook activity..."
echo "Press Ctrl+C to stop"
echo ""
echo "Watching for:"
echo "  - participant_joined events"
echo "  - participant_left events"
echo "  - room_started events"
echo "  - room_finished events"
echo ""
echo "========================================="
echo ""

# Follow Laravel logs and filter for webhook-related events
tail -f storage/logs/laravel.log | grep --line-buffered -E "WEBHOOK|participant_joined|participant_left|room_started|room_finished|MeetingAttendanceEvent"
