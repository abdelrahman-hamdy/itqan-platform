#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  WireChat Services Restart Script${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Change to project directory
cd /Users/abdelrahmanhamdy/web/itqan-platform

# Step 1: Kill existing processes
echo -e "${YELLOW}[1/4] Stopping existing services...${NC}"

# Kill Reverb processes
REVERB_PIDS=$(pgrep -f "reverb:start")
if [ -n "$REVERB_PIDS" ]; then
    echo -e "  → Killing Reverb processes: $REVERB_PIDS"
    kill -9 $REVERB_PIDS 2>/dev/null || true
    echo -e "  ${GREEN}✓${NC} Reverb stopped"
else
    echo -e "  ℹ No Reverb processes found"
fi

# Kill Queue workers
QUEUE_PIDS=$(pgrep -f "queue:work|queue:listen")
if [ -n "$QUEUE_PIDS" ]; then
    echo -e "  → Killing Queue workers: $QUEUE_PIDS"
    kill -9 $QUEUE_PIDS 2>/dev/null || true
    echo -e "  ${GREEN}✓${NC} Queue workers stopped"
else
    echo -e "  ℹ No Queue workers found"
fi

# Kill watch scripts
WATCH_PIDS=$(pgrep -f "watch-reverb|tail.*reverb")
if [ -n "$WATCH_PIDS" ]; then
    echo -e "  → Killing watch scripts: $WATCH_PIDS"
    kill -9 $WATCH_PIDS 2>/dev/null || true
    echo -e "  ${GREEN}✓${NC} Watch scripts stopped"
else
    echo -e "  ℹ No watch scripts found"
fi

# Wait for processes to fully terminate
sleep 2

echo ""
echo -e "${YELLOW}[2/4] Clearing caches...${NC}"
php artisan config:clear >/dev/null 2>&1
php artisan cache:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
echo -e "  ${GREEN}✓${NC} Caches cleared"

echo ""
echo -e "${YELLOW}[3/4] Starting Laravel Reverb...${NC}"

# Check if we're using HTTPS/WSS
REVERB_SCHEME=$(grep "^REVERB_SCHEME=" .env | head -1 | cut -d'=' -f2)
REVERB_PORT=$(grep "^REVERB_PORT=" .env | head -1 | cut -d'=' -f2)
REVERB_HOST=$(grep "^REVERB_HOST=" .env | head -1 | cut -d'=' -f2)

echo -e "  → Configuration:"
echo -e "    Scheme: ${BLUE}$REVERB_SCHEME${NC}"
echo -e "    Host: ${BLUE}$REVERB_HOST${NC}"
echo -e "    Port: ${BLUE}$REVERB_PORT${NC}"

# Start Reverb in background with output to log file
LOG_DIR="/tmp/wirechat-logs"
mkdir -p $LOG_DIR

echo -e "  → Starting Reverb server with verbose logging..."
nohup php artisan reverb:start --host=0.0.0.0 --port=$REVERB_PORT --debug > "$LOG_DIR/reverb.log" 2>&1 &
REVERB_PID=$!

# Wait a moment and check if Reverb started successfully
sleep 3

if ps -p $REVERB_PID > /dev/null 2>&1; then
    echo -e "  ${GREEN}✓${NC} Reverb started successfully (PID: $REVERB_PID)"
    echo -e "  → Log file: ${BLUE}$LOG_DIR/reverb.log${NC}"
else
    echo -e "  ${RED}✗${NC} Reverb failed to start. Check logs:"
    echo -e "    tail -f $LOG_DIR/reverb.log"
    exit 1
fi

echo ""
echo -e "${YELLOW}[4/4] Starting Queue Workers...${NC}"

# Start messages queue worker with verbose logging
nohup php artisan queue:work --queue=messages,default --tries=3 --timeout=90 --verbose --no-interaction > "$LOG_DIR/queue-messages.log" 2>&1 &
QUEUE_PID=$!

sleep 2

if ps -p $QUEUE_PID > /dev/null 2>&1; then
    echo -e "  ${GREEN}✓${NC} Queue worker started (PID: $QUEUE_PID)"
    echo -e "  → Log file: ${BLUE}$LOG_DIR/queue-messages.log${NC}"
else
    echo -e "  ${RED}✗${NC} Queue worker failed to start"
    exit 1
fi

echo ""
echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}  ✓ All services started successfully!${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""
echo -e "Service Status:"
echo -e "  • Reverb Server: ${GREEN}Running${NC} (PID: $REVERB_PID)"
echo -e "  • Queue Worker: ${GREEN}Running${NC} (PID: $QUEUE_PID)"
echo ""
echo -e "Access Chat:"
echo -e "  → ${BLUE}https://$REVERB_HOST/chats${NC}"
echo ""
echo -e "Monitor Logs:"
echo -e "  → Reverb:  ${YELLOW}tail -f $LOG_DIR/reverb.log${NC}"
echo -e "  → Queue:   ${YELLOW}tail -f $LOG_DIR/queue-messages.log${NC}"
echo ""
echo -e "Stop Services:"
echo -e "  → Run: ${YELLOW}./restart-chat.sh stop${NC}"
echo ""

# Create stop script if it doesn't exist
cat > /Users/abdelrahmanhamdy/web/itqan-platform/stop-chat.sh << 'EOF'
#!/bin/bash
echo "Stopping WireChat services..."
pkill -f "reverb:start" && echo "✓ Reverb stopped"
pkill -f "queue:work" && echo "✓ Queue workers stopped"
pkill -f "watch-reverb" && echo "✓ Watch scripts stopped"
echo "All services stopped."
EOF

chmod +x /Users/abdelrahmanhamdy/web/itqan-platform/stop-chat.sh
