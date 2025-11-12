#!/bin/bash

# Real-Time Chat Monitoring Script
# This script monitors all chat-related logs in real-time

echo "🔍 Chat Real-Time Monitor"
echo "========================="
echo ""
echo "Monitoring for chat activity..."
echo "Send a message in the chat to see the flow"
echo ""
echo "Press Ctrl+C to stop"
echo ""
echo "───────────────────────────────────────────────────────────────"
echo ""

# Clear the log first to see only new activity
> storage/logs/chat-debug.log

# Tail the Laravel log and filter for broadcast-related entries
tail -f storage/logs/laravel.log | grep --line-buffered -E "\[BROADCAST|MessageSent|📡|🔔|📺|✅|❌|🚀|🎉" --color=always
