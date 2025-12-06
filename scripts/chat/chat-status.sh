#!/bin/bash

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  WireChat Services Status${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Check Reverb
REVERB_PID=$(pgrep -f "reverb:start")
if [ -n "$REVERB_PID" ]; then
    echo -e "Reverb Server: ${GREEN}✓ Running${NC} (PID: $REVERB_PID)"
    echo -e "  Port: 8085 (HTTPS/WSS)"
else
    echo -e "Reverb Server: ${RED}✗ Not Running${NC}"
fi

# Check Queue Workers
QUEUE_PIDS=$(pgrep -f "queue:work")
if [ -n "$QUEUE_PIDS" ]; then
    QUEUE_COUNT=$(echo "$QUEUE_PIDS" | wc -l | tr -d ' ')
    echo -e "Queue Workers: ${GREEN}✓ Running${NC} ($QUEUE_COUNT worker(s))"
    echo "$QUEUE_PIDS" | while read pid; do
        echo -e "  PID: $pid"
    done
else
    echo -e "Queue Workers: ${RED}✗ Not Running${NC}"
fi

echo ""
echo -e "${BLUE}Logs:${NC}"
if [ -f "/tmp/wirechat-logs/reverb.log" ]; then
    echo -e "  Reverb: ${YELLOW}tail -f /tmp/wirechat-logs/reverb.log${NC}"
fi
if [ -f "/tmp/wirechat-logs/queue-messages.log" ]; then
    echo -e "  Queue:  ${YELLOW}tail -f /tmp/wirechat-logs/queue-messages.log${NC}"
fi

echo ""
echo -e "${BLUE}Access:${NC}"
echo -e "  Chat Interface: ${GREEN}https://itqan-platform.test/chats${NC}"
echo ""
