#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

LOG_DIR="/tmp/wirechat-logs"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  WireChat Real-Time Monitor${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Check if services are running
REVERB_PID=$(pgrep -f "reverb:start")
QUEUE_PID=$(pgrep -f "queue:work")

if [ -z "$REVERB_PID" ]; then
    echo -e "${RED}❌ Reverb is NOT running!${NC}"
    echo -e "   Start it with: ${YELLOW}./restart-chat.sh${NC}"
    exit 1
fi

if [ -z "$QUEUE_PID" ]; then
    echo -e "${RED}❌ Queue worker is NOT running!${NC}"
    echo -e "   Start it with: ${YELLOW}./restart-chat.sh${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Reverb running${NC} (PID: $REVERB_PID)"
echo -e "${GREEN}✓ Queue worker running${NC} (PID: $QUEUE_PID)"
echo ""
echo -e "${YELLOW}📊 Monitoring both logs...${NC}"
echo -e "${CYAN}Press Ctrl+C to stop${NC}"
echo ""
echo -e "${BLUE}Legend:${NC}"
echo -e "  ${PURPLE}[REVERB]${NC} - WebSocket server events"
echo -e "  ${CYAN}[QUEUE]${NC}  - Message broadcast events"
echo ""
echo -e "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Create logs directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Function to process and colorize Reverb logs
process_reverb() {
    while IFS= read -r line; do
        timestamp=$(date "+%H:%M:%S")
        if [[ $line == *"Starting"* ]] || [[ $line == *"server"* ]]; then
            echo -e "${PURPLE}[$timestamp][REVERB]${NC} ${GREEN}$line${NC}"
        elif [[ $line == *"error"* ]] || [[ $line == *"Error"* ]] || [[ $line == *"failed"* ]]; then
            echo -e "${PURPLE}[$timestamp][REVERB]${NC} ${RED}$line${NC}"
        elif [[ $line == *"connection"* ]] || [[ $line == *"subscribe"* ]]; then
            echo -e "${PURPLE}[$timestamp][REVERB]${NC} ${YELLOW}$line${NC}"
        else
            echo -e "${PURPLE}[$timestamp][REVERB]${NC} $line"
        fi
    done
}

# Function to process and colorize Queue logs
process_queue() {
    while IFS= read -r line; do
        timestamp=$(date "+%H:%M:%S")
        if [[ $line == *"Processing"* ]] || [[ $line == *"Processed"* ]]; then
            echo -e "${CYAN}[$timestamp][QUEUE]${NC} ${GREEN}$line${NC}"
        elif [[ $line == *"MessageCreated"* ]]; then
            echo -e "${CYAN}[$timestamp][QUEUE]${NC} ${YELLOW}📨 $line${NC}"
        elif [[ $line == *"Failed"* ]] || [[ $line == *"error"* ]] || [[ $line == *"Error"* ]]; then
            echo -e "${CYAN}[$timestamp][QUEUE]${NC} ${RED}$line${NC}"
        else
            echo -e "${CYAN}[$timestamp][QUEUE]${NC} $line"
        fi
    done
}

# Tail both logs simultaneously with color coding
tail -f "$LOG_DIR/reverb.log" 2>/dev/null | process_reverb &
REVERB_TAIL_PID=$!

tail -f "$LOG_DIR/queue-messages.log" 2>/dev/null | process_queue &
QUEUE_TAIL_PID=$!

# Trap Ctrl+C to kill tail processes
trap "kill $REVERB_TAIL_PID $QUEUE_TAIL_PID 2>/dev/null; echo ''; echo -e '${YELLOW}Monitoring stopped${NC}'; exit" INT TERM

# Keep script running
wait
