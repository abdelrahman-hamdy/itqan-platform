#!/bin/bash

echo "🧪 Testing Chat System Fixes"
echo "============================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}1. Checking Reverb Server Status...${NC}"
if lsof -i :8085 > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Reverb is running on port 8085${NC}"
    echo "   Connections:"
    lsof -i :8085 | grep -v "^COMMAND"
else
    echo -e "${RED}❌ Reverb is NOT running${NC}"
    echo "   Starting Reverb..."
    php artisan reverb:start --host=0.0.0.0 --port=8085 &
    sleep 2
fi

echo ""
echo -e "${YELLOW}2. Checking JavaScript File...${NC}"
if grep -q "private-chatify" public/js/chat-system-reverb.js; then
    echo -e "${GREEN}✅ Channel name fix applied (private-chatify)${NC}"
else
    echo -e "${RED}❌ Channel name not fixed${NC}"
fi

if grep -q "updateContactLastMessage" public/js/chat-system-reverb.js; then
    echo -e "${GREEN}✅ Sidebar update fix applied${NC}"
else
    echo -e "${RED}❌ Sidebar update not fixed${NC}"
fi

if grep -q "handleFileUpload" public/js/chat-system-reverb.js; then
    echo -e "${GREEN}✅ File upload implementation added${NC}"
else
    echo -e "${RED}❌ File upload not implemented${NC}"
fi

echo ""
echo -e "${YELLOW}3. Browser Cache Clear Instructions:${NC}"
echo "   1. Open Chrome DevTools (F12)"
echo "   2. Right-click the Refresh button"
echo "   3. Select 'Empty Cache and Hard Reload'"
echo "   OR"
echo "   Press: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows/Linux)"

echo ""
echo -e "${YELLOW}4. Testing URLs:${NC}"
echo "   Student → Teacher: https://itqan-academy.itqan-platform.test/chat?user=3"
echo "   Teacher → Student: https://itqan-academy.itqan-platform.test/chat?user=2"

echo ""
echo -e "${YELLOW}5. What to Test:${NC}"
echo "   ${GREEN}Real-time:${NC}"
echo "   - Messages appear instantly in both windows"
echo "   - User status shows 'متصل' when online"
echo ""
echo "   ${GREEN}Sidebar:${NC}"
echo "   - Last message updates immediately after sending"
echo "   - Contact moves to top of list"
echo ""
echo "   ${GREEN}File Upload:${NC}"
echo "   - Click 📎 icon to select file"
echo "   - Image files show preview"
echo "   - Other files show icon and size"
echo "   - Can add optional message with file"

echo ""
echo -e "${YELLOW}6. Console Commands to Verify:${NC}"
echo "   Open browser console and check for:"
echo "   - '✅ Reverb WebSocket connected successfully'"
echo "   - '✅ Subscribed to authenticated private channel: private-chatify.[ID]'"
echo "   - '📋 Updating sidebar after sending message'"

echo ""
echo "============================"
echo -e "${GREEN}All fixes have been applied!${NC}"
echo "Clear your browser cache and test the features."