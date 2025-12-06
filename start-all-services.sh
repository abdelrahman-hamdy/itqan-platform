#!/bin/bash

echo "🚀 Starting All Itqan Platform Services..."
echo "=========================================="

# Check if Reverb is running
if pgrep -f "reverb:start" > /dev/null; then
    echo "✅ Reverb is already running"
else
    echo "🔵 Starting Reverb WebSocket server..."
    php artisan reverb:start --host=0.0.0.0 --port=8085 > storage/logs/reverb.log 2>&1 &
    sleep 2
    if pgrep -f "reverb:start" > /dev/null; then
        echo "✅ Reverb started (port 8085)"
    else
        echo "❌ Failed to start Reverb"
    fi
fi

# Check if Queue worker is running
if pgrep -f "queue:listen" > /dev/null; then
    echo "✅ Queue worker is already running"
else
    echo "🔵 Starting Queue worker..."
    php artisan queue:listen --tries=1 --timeout=90 > storage/logs/queue.log 2>&1 &
    sleep 2
    if pgrep -f "queue:listen" > /dev/null; then
        echo "✅ Queue worker started"
    else
        echo "❌ Failed to start Queue worker"
    fi
fi

# Check if Scheduler is running (CRITICAL for notifications!)
if pgrep -f "schedule:work" > /dev/null; then
    echo "✅ Scheduler is already running"
else
    echo "🔵 Starting Scheduler (REQUIRED for session notifications)..."
    php artisan schedule:work > storage/logs/scheduler.log 2>&1 &
    sleep 2
    if pgrep -f "schedule:work" > /dev/null; then
        echo "✅ Scheduler started (runs every minute)"
    else
        echo "❌ Failed to start Scheduler"
    fi
fi

echo ""
echo "=========================================="
echo "✅ All services started!"
echo ""
echo "Running Services:"
ps aux | grep -E "reverb:start|queue:listen|schedule:work" | grep -v grep | awk '{print "  • " $11 " " $12 " " $13 " " $14}'
echo ""
echo "Service Logs:"
echo "  • Reverb:    tail -f storage/logs/reverb.log"
echo "  • Queue:     tail -f storage/logs/queue.log"
echo "  • Scheduler: tail -f storage/logs/scheduler.log"
echo "  • Laravel:   php artisan pail"
echo ""
echo "Stop all services:"
echo "  pkill -f 'reverb:start|queue:listen|schedule:work'"
echo "=========================================="
