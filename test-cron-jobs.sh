#!/bin/bash

# Cron Jobs Testing Script
# This script tests all cron jobs to ensure they're working properly

echo "======================================"
echo "🧪 Cron Jobs Testing Script"
echo "======================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Base path
BASE_PATH="/Users/abdelrahmanhamdy/web/itqan-platform"
LOG_PATH="$BASE_PATH/storage/logs/cron"

# Test counter
PASSED=0
FAILED=0

# Function to test a command
test_command() {
    local CMD=$1
    local NAME=$2
    local LOG_FILE="$LOG_PATH/${CMD}.log"

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Testing: $NAME"
    echo "Command: php artisan $CMD"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    # Get the last STARTED timestamp before running
    BEFORE_TIME=$(tail -20 "$LOG_FILE" 2>/dev/null | grep "STARTED" | tail -1 | sed -n 's/^\[\([^]]*\)\].*/\1/p' || echo "")

    # Run the command
    php "$BASE_PATH/artisan" $CMD > /dev/null 2>&1
    EXIT_CODE=$?

    # Wait a moment for logs to write
    sleep 1

    # Get the last STARTED and FINISHED timestamps after running
    AFTER_START=$(tail -20 "$LOG_FILE" 2>/dev/null | grep "STARTED" | tail -1 | sed -n 's/^\[\([^]]*\)\].*/\1/p' || echo "")
    AFTER_FINISH=$(tail -20 "$LOG_FILE" 2>/dev/null | grep "FINISHED" | tail -1 | sed -n 's/^\[\([^]]*\)\].*/\1/p' || echo "")

    # Check if new logs were created
    if [ "$AFTER_START" != "$BEFORE_TIME" ]; then
        if [ ! -z "$AFTER_FINISH" ]; then
            echo -e "${GREEN}✅ PASSED${NC}"
            echo "  - Exit Code: $EXIT_CODE"
            echo "  - Log File: $LOG_FILE"
            echo "  - Started: $AFTER_START"
            echo "  - Finished: $AFTER_FINISH"
            ((PASSED++))
        else
            echo -e "${RED}❌ FAILED${NC}"
            echo "  - Command started but did not finish logging"
            echo "  - Exit Code: $EXIT_CODE"
            ((FAILED++))
        fi
    else
        echo -e "${RED}❌ FAILED${NC}"
        echo "  - No new logs generated"
        echo "  - Exit Code: $EXIT_CODE"
        ((FAILED++))
    fi

    echo ""
}

echo "📋 Starting Cron Jobs Tests..."
echo ""

# Test all cron jobs
test_command "sessions:manage-meetings" "Session Meeting Management"
test_command "academic-sessions:manage-meetings" "Academic Session Meeting Management"
test_command "meetings:create-scheduled" "Create Scheduled Meetings"
test_command "meetings:cleanup-expired" "Cleanup Expired Meetings"
test_command "sessions:update-statuses" "Update Session Statuses"

# Summary
echo "======================================"
echo "📊 Test Summary"
echo "======================================"
echo -e "Total Tests: $((PASSED + FAILED))"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All cron jobs are working correctly!${NC}"
    exit 0
else
    echo -e "${RED}⚠️  Some cron jobs failed. Check the details above.${NC}"
    exit 1
fi
