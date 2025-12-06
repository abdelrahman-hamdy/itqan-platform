#!/bin/bash

# Notification System Startup Script
# This script starts all required services for the notification system to work

echo "🔔 Starting Itqan Platform Notification System..."
echo "=================================================="

# Check if Reverb is already running
if pgrep -f "reverb:start" > /dev/null; then
    echo "✅ Reverb is already running"
else
    echo "🚀 Starting Reverb WebSocket server..."
    php artisan reverb:start --host=0.0.0.0 --port=8085 > storage/logs/reverb.log 2>&1 &
    sleep 2
    if pgrep -f "reverb:start" > /dev/null; then
        echo "✅ Reverb started successfully on port 8085"
    else
        echo "❌ Failed to start Reverb. Check storage/logs/reverb.log for details"
        exit 1
    fi
fi

# Check if Queue worker is already running
if pgrep -f "queue:listen" > /dev/null; then
    echo "✅ Queue worker is already running"
else
    echo "🚀 Starting Queue worker..."
    php artisan queue:listen --tries=1 --timeout=90 > storage/logs/queue.log 2>&1 &
    sleep 2
    if pgrep -f "queue:listen" > /dev/null; then
        echo "✅ Queue worker started successfully"
    else
        echo "❌ Failed to start Queue worker. Check storage/logs/queue.log for details"
        exit 1
    fi
fi

# Optional: Start scheduler worker (if not using cron)
if ! pgrep -f "schedule:work" > /dev/null; then
    echo "📅 Starting Schedule worker..."
    php artisan schedule:work > storage/logs/scheduler.log 2>&1 &
    sleep 2
    if pgrep -f "schedule:work" > /dev/null; then
        echo "✅ Scheduler started successfully"
    else
        echo "⚠️  Scheduler failed to start (this is optional if you're using cron)"
    fi
else
    echo "✅ Scheduler is already running"
fi

echo ""
echo "=================================================="
echo "✅ All notification services are running!"
echo ""
echo "Running Services:"
echo "  • Reverb WebSocket (port 8085)"
echo "  • Queue Worker"
echo "  • Scheduler Worker"
echo ""
echo "Logs:"
echo "  • Reverb:    tail -f storage/logs/reverb.log"
echo "  • Queue:     tail -f storage/logs/queue.log"
echo "  • Scheduler: tail -f storage/logs/scheduler.log"
echo "  • Laravel:   php artisan pail"
echo ""
echo "To test notifications:"
echo "  php artisan notifications:test --type=all"
echo ""
echo "To stop all services:"
echo "  pkill -f 'reverb:start|queue:listen|schedule:work'"
echo "=================================================="
