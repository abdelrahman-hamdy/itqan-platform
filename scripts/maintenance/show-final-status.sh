#!/bin/bash

echo "═══════════════════════════════════════════════"
echo "🎉 WIRECHAT REAL-TIME SETUP COMPLETE"
echo "═══════════════════════════════════════════════"
echo ""

echo "📊 FINAL STATUS:"
echo ""

echo "Backend Test:"
LAST_TEST=$(grep -l "Broadcast dispatched successfully" storage/logs/laravel.log 2>/dev/null)
if [ -n "$LAST_TEST" ]; then
    echo "  ✅ Test message sent successfully"
    CONV_ID=$(grep "Navigate to" test-wirechat-message.php 2>/dev/null | grep -o "chat/[0-9]*" | cut -d/ -f2 | tail -1)
    if [ -n "$CONV_ID" ]; then
        echo "  📍 Test conversation ID: $CONV_ID"
    fi
else
    echo "  ⚠️  No recent test found"
fi
echo ""

echo "WireChat Integration:"
if [ -f "public/js/wirechat-realtime.js" ]; then
    VERSION=$(grep "WireChat Real-Time Bridge" public/js/wirechat-realtime.js | grep -o "v[0-9]" | head -1)
    FILE_SIZE=$(ls -lh public/js/wirechat-realtime.js | awk '{print $5}')
    echo "  ✅ wirechat-realtime.js: $FILE_SIZE ($VERSION)"
else
    echo "  ❌ wirechat-realtime.js: NOT FOUND"
fi

LINE_NUM=$(grep -n "wirechat-realtime.js" resources/views/chat/wirechat-content.blade.php 2>/dev/null | cut -d: -f1)
if [ -n "$LINE_NUM" ]; then
    echo "  ✅ Script loaded in view: Line $LINE_NUM"
else
    echo "  ❌ Script not loaded in view"
fi

if [ -f "app/Events/WireChat/MessageCreatedNow.php" ]; then
    echo "  ✅ MessageCreatedNow event: Created"
else
    echo "  ❌ MessageCreatedNow event: NOT FOUND"
fi

if grep -q "fixWirechatBroadcasting" app/Providers/WirechatServiceProvider.php 2>/dev/null; then
    echo "  ✅ Broadcast fix: Installed"
else
    echo "  ❌ Broadcast fix: NOT INSTALLED"
fi
echo ""

echo "Services:"
REVERB_PID=$(ps aux | grep "reverb:start" | grep -v grep | awk '{print $2}' | head -1)
QUEUE_PID=$(ps aux | grep "queue:work" | grep -v grep | awk '{print $2}' | head -1)

if [ -n "$REVERB_PID" ]; then
    echo "  ✅ Reverb: Running (PID: $REVERB_PID)"
else
    echo "  ❌ Reverb: NOT RUNNING"
fi

if [ -n "$QUEUE_PID" ]; then
    echo "  ✅ Queue Worker: Running (PID: $QUEUE_PID)"
else
    echo "  ⚠️  Queue Worker: NOT RUNNING (optional)"
fi

echo ""
echo "═══════════════════════════════════════════════"
echo "📝 NEXT STEP: TEST IN BROWSER!"
echo "═══════════════════════════════════════════════"
echo ""
echo "⚠️  IMPORTANT: Clear browser cache first!"
echo "    (or use incognito mode: Ctrl+Shift+N)"
echo ""
echo "📖 Read: WIRECHAT_SETUP_COMPLETE.md"
echo ""

# Get the conversation ID from the last test
CONV_ID=$(grep "conversation ID:" storage/logs/laravel.log 2>/dev/null | tail -1 | grep -o "[0-9]*" | tail -1)
if [ -n "$CONV_ID" ]; then
    echo "🎯 Open chat (from last test):"
    echo "    https://2.itqan-platform.test/chat/$CONV_ID"
    echo ""
    echo "🧪 Send test message:"
    echo "    ./test-message-flow.sh"
else
    echo "🧪 Run test to create conversation:"
    echo "    ./test-message-flow.sh"
    echo ""
    echo "   Then open the URL shown in the output"
fi
echo ""
