#!/bin/bash

# Chat Services Restart Script
# This script kills all running chat-related services and restarts them properly

echo "🔴 Stopping all chat services..."
echo "================================"

# 1. Kill all Reverb processes
echo "Killing Reverb processes..."
pkill -f "artisan reverb:start" 2>/dev/null
lsof -ti:8085 | xargs kill -9 2>/dev/null
sleep 2

# 2. Kill all Queue workers
echo "Killing Queue workers..."
pkill -f "artisan queue:work" 2>/dev/null
pkill -f "artisan queue:listen" 2>/dev/null
sleep 2

# 3. Kill any hanging Horizon processes (if used)
echo "Killing Horizon processes..."
pkill -f "artisan horizon" 2>/dev/null
sleep 1

echo ""
echo "✅ All services stopped"
echo ""

# 4. Clear cache and optimize
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# 5. Re-cache for performance
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache

echo ""
echo "✅ Cache cleared"
echo ""

# 6. Clear stuck jobs from queue
echo "🧹 Clearing stuck queue jobs..."
php artisan queue:flush

# 7. Check for failed jobs
FAILED_COUNT=$(php artisan queue:failed | grep -c "| [0-9]")
if [ $FAILED_COUNT -gt 0 ]; then
    echo "⚠️  Found $FAILED_COUNT failed jobs. Retrying..."
    php artisan queue:retry all
fi

echo ""
echo "🟢 Starting services..."
echo "================================"

# 8. Start Reverb in background
echo "Starting Reverb WebSocket server..."
php artisan reverb:start > storage/logs/reverb.log 2>&1 &
REVERB_PID=$!
sleep 3

# Check if Reverb started
if ps -p $REVERB_PID > /dev/null; then
    echo "✅ Reverb started (PID: $REVERB_PID)"

    # Verify port is listening
    if lsof -i:8085 > /dev/null 2>&1; then
        echo "✅ Reverb listening on port 8085"
    else
        echo "❌ Reverb not listening on port 8085"
    fi
else
    echo "❌ Failed to start Reverb"
fi

# 9. Start Queue worker in background
echo "Starting Queue worker..."
php artisan queue:work --daemon --tries=3 --timeout=90 > storage/logs/queue.log 2>&1 &
QUEUE_PID=$!
sleep 2

# Check if Queue worker started
if ps -p $QUEUE_PID > /dev/null; then
    echo "✅ Queue worker started (PID: $QUEUE_PID)"
else
    echo "❌ Failed to start Queue worker"
fi

echo ""
echo "📊 Service Status:"
echo "================================"

# Check Reverb
if lsof -i:8085 > /dev/null 2>&1; then
    echo "✅ Reverb WebSocket: RUNNING on port 8085"
    REVERB_PID=$(lsof -ti:8085)
    echo "   PID: $REVERB_PID"
else
    echo "❌ Reverb WebSocket: NOT RUNNING"
fi

# Check Queue Worker
if pgrep -f "queue:work" > /dev/null; then
    echo "✅ Queue Worker: RUNNING"
    QUEUE_PID=$(pgrep -f "queue:work")
    echo "   PID: $QUEUE_PID"
else
    echo "❌ Queue Worker: NOT RUNNING"
fi

# Check pending jobs
PENDING_JOBS=$(php artisan queue:monitor 2>/dev/null | grep -o "[0-9]* pending" | grep -o "[0-9]*" || echo "0")
echo "📋 Queue Status: $PENDING_JOBS pending jobs"

echo ""
echo "📝 Log files:"
echo "================================"
echo "Reverb:      tail -f storage/logs/reverb.log"
echo "Queue:       tail -f storage/logs/queue.log"
echo "Laravel:     tail -f storage/logs/laravel.log"

echo ""
echo "✅ Chat services restart complete!"
echo ""
echo "🧪 Test your chat now. Messages should deliver in real-time."
echo ""
