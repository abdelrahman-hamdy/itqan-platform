#!/bin/bash

echo "🔍 Verifying WireChat Real-Time Fix"
echo "===================================="
echo ""

# Check for old script references
echo "1️⃣  Checking for old script references..."
OLD_REFS=$(grep -r "chat-system-reverb.js" resources/ --include="*.blade.php" 2>/dev/null | grep -v "{{--" | wc -l | xargs)
if [ "$OLD_REFS" = "0" ]; then
    echo "  ✅ No active references to old script"
else
    echo "  ⚠️  Found $OLD_REFS active references"
    grep -r "chat-system-reverb.js" resources/ --include="*.blade.php" 2>/dev/null | grep -v "{{--"
fi
echo ""

# Check new script exists
echo "2️⃣  Checking new script..."
if [ -f "public/js/wirechat-realtime.js" ]; then
    VERSION=$(grep "WireChat Real-Time Bridge" public/js/wirechat-realtime.js | grep -o "v[0-9]" | head -1)
    SIZE=$(ls -lh public/js/wirechat-realtime.js | awk '{print $5}')
    echo "  ✅ wirechat-realtime.js exists ($SIZE, $VERSION)"
else
    echo "  ❌ wirechat-realtime.js NOT FOUND"
fi
echo ""

# Check new script is loaded in views
echo "3️⃣  Checking new script is loaded..."
NEW_REFS=$(grep -r "wirechat-realtime.js" resources/ --include="*.blade.php" 2>/dev/null | wc -l | xargs)
if [ "$NEW_REFS" -gt "0" ]; then
    echo "  ✅ Found in $NEW_REFS view files:"
    grep -r "wirechat-realtime.js" resources/ --include="*.blade.php" 2>/dev/null | cut -d: -f1 | sort -u | while read file; do
        echo "     - $(basename $file)"
    done
else
    echo "  ❌ New script not loaded in any views"
fi
echo ""

# Check services
echo "4️⃣  Checking services..."
REVERB_PID=$(ps aux | grep "reverb:start" | grep -v grep | awk '{print $2}' | head -1)
if [ -n "$REVERB_PID" ]; then
    echo "  ✅ Reverb running (PID: $REVERB_PID)"
else
    echo "  ❌ Reverb NOT RUNNING"
fi
echo ""

echo "===================================="
if [ "$OLD_REFS" = "0" ] && [ -f "public/js/wirechat-realtime.js" ] && [ "$NEW_REFS" -gt "0" ] && [ -n "$REVERB_PID" ]; then
    echo "✅ All checks passed!"
    echo ""
    echo "🚀 Now test in browser:"
    echo "   1. Clear cache (Ctrl+Shift+Del) or use incognito"
    echo "   2. Open: https://2.itqan-platform.test/chat/3"
    echo "   3. Check console for 'WireChat Real-Time Bridge (v2)'"
    echo "   4. Run: ./test-message-flow.sh"
else
    echo "❌ Some checks failed - review above"
fi
echo ""
