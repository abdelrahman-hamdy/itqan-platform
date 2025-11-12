#!/bin/bash

echo "🧪 Testing WireChat Real-Time System"
echo "====================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Reverb is running
echo "1️⃣  Checking Reverb WebSocket Server..."
REVERB_PID=$(ps aux | grep "reverb:start" | grep -v grep | awk '{print $2}' | head -1)
if [ -n "$REVERB_PID" ]; then
    echo -e "${GREEN}✅ Reverb is running (PID: $REVERB_PID)${NC}"
else
    echo -e "${RED}❌ Reverb is NOT running${NC}"
    echo "   Run: ./restart-chat-services.sh"
    exit 1
fi

echo ""

# Check if queue worker is running
echo "2️⃣  Checking Queue Worker..."
QUEUE_PID=$(ps aux | grep "queue:work" | grep -v grep | awk '{print $2}' | head -1)
if [ -n "$QUEUE_PID" ]; then
    echo -e "${GREEN}✅ Queue worker is running (PID: $QUEUE_PID)${NC}"
else
    echo -e "${YELLOW}⚠️  Queue worker is NOT running (optional)${NC}"
fi

echo ""

# Check for Chatify remnants
echo "3️⃣  Checking for Chatify remnants..."
CHATIFY_FILES=$(find . -type f \( -name "*hatify*" -o -name "*ChMessage*" \) 2>/dev/null | grep -v "node_modules" | grep -v "vendor" | grep -v "chatify-backup" | grep -v ".git" | grep -v "\.md" | grep -v "\.sh" | wc -l | xargs)
CHATIFY_DIRS=$(find . -type d -name "*hatify*" 2>/dev/null | grep -v "node_modules" | grep -v "vendor" | grep -v ".git" | grep -v "chatify-backup" | wc -l | xargs)

if [ "$CHATIFY_FILES" = "0" ] && [ "$CHATIFY_DIRS" = "0" ]; then
    echo -e "${GREEN}✅ No Chatify remnants found${NC}"
else
    echo -e "${YELLOW}⚠️  Found $CHATIFY_FILES files and $CHATIFY_DIRS directories${NC}"
fi

echo ""

# Check WireChat script exists
echo "4️⃣  Checking WireChat real-time script..."
if [ -f "public/js/wirechat-realtime.js" ]; then
    FILE_SIZE=$(ls -lh public/js/wirechat-realtime.js | awk '{print $5}')
    echo -e "${GREEN}✅ wirechat-realtime.js exists ($FILE_SIZE)${NC}"
else
    echo -e "${RED}❌ wirechat-realtime.js NOT found${NC}"
    exit 1
fi

echo ""

# Check if script is loaded in view
echo "5️⃣  Checking if script is loaded in chat view..."
if grep -q "wirechat-realtime.js" resources/views/chat/wirechat-content.blade.php 2>/dev/null; then
    echo -e "${GREEN}✅ Script is loaded in wirechat-content.blade.php${NC}"
else
    echo -e "${RED}❌ Script NOT loaded in view${NC}"
    exit 1
fi

echo ""

# Check chat-system-reverb.js is removed
echo "6️⃣  Checking old Chatify script is removed..."
if [ ! -f "public/js/chat-system-reverb.js" ]; then
    echo -e "${GREEN}✅ Old chat-system-reverb.js is removed${NC}"
else
    echo -e "${RED}❌ Old chat-system-reverb.js still exists!${NC}"
    echo "   Remove it: rm public/js/chat-system-reverb.js"
fi

echo ""

# Check routes
echo "7️⃣  Checking chat routes..."
WIRECHAT_ROUTES=$(php artisan route:list 2>/dev/null | grep -i "wirechat" | wc -l | xargs)
if [ "$WIRECHAT_ROUTES" -gt "0" ]; then
    echo -e "${GREEN}✅ WireChat routes are active ($WIRECHAT_ROUTES routes)${NC}"
else
    echo -e "${YELLOW}⚠️  No WireChat routes found${NC}"
fi

echo ""
echo "════════════════════════════════════"
echo ""

# Final verdict
ALL_GOOD=true
if [ -z "$REVERB_PID" ]; then ALL_GOOD=false; fi
if [ ! -f "public/js/wirechat-realtime.js" ]; then ALL_GOOD=false; fi
if [ -f "public/js/chat-system-reverb.js" ]; then ALL_GOOD=false; fi

if [ "$ALL_GOOD" = true ]; then
    echo -e "${GREEN}🎉 All checks passed!${NC}"
    echo ""
    echo "📝 Next Steps:"
    echo "   1. Clear your browser cache (Ctrl+Shift+Del)"
    echo "      OR open chat in incognito mode (Ctrl+Shift+N)"
    echo "   2. Open: https://2.itqan-platform.test/chat"
    echo "   3. Open browser console (F12)"
    echo "   4. You should see: '🔗 WireChat Real-Time Bridge'"
    echo "   5. Send a test message:"
    echo "      ./test-message-flow.sh"
    echo ""
    echo -e "${YELLOW}⚠️  IMPORTANT: Browser cache MUST be cleared!${NC}"
    echo "   The old chat-system-reverb.js is cached in your browser."
else
    echo -e "${RED}❌ Some checks failed${NC}"
    echo ""
    echo "Fix the issues above and try again."
fi

echo ""
