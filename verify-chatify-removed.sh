#!/bin/bash

echo "🔍 Verifying Chatify is Completely Removed"
echo "==========================================="
echo ""

ERRORS=0

# 1. Check JavaScript files
echo "1️⃣  JavaScript Files:"
if [ -f "public/js/chat-system-reverb.js" ]; then
    echo "   ❌ chat-system-reverb.js still exists"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ chat-system-reverb.js removed"
fi

# 2. Check routes
echo ""
echo "2️⃣  Routes:"
if [ -d "routes/chatify" ]; then
    echo "   ❌ routes/chatify/ still exists"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ routes/chatify/ removed"
fi

# 3. Check controllers
echo ""
echo "3️⃣  Controllers:"
if [ -d "app/Http/Controllers/vendor/Chatify" ]; then
    echo "   ❌ Chatify controllers still exist"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ Chatify controllers removed"
fi

# 4. Check models
echo ""
echo "4️⃣  Models:"
CHATIFY_MODELS=(
    "app/Models/ChMessage.php"
    "app/Models/ChFavorite.php"
    "app/Models/ChatGroup.php"
)

for model in "${CHATIFY_MODELS[@]}"; do
    if [ -f "$model" ]; then
        echo "   ❌ $model still exists"
        ERRORS=$((ERRORS + 1))
    fi
done

if [ $ERRORS -eq 0 ]; then
    echo "   ✅ All Chatify models removed"
fi

# 5. Check database tables
echo ""
echo "5️⃣  Database Tables:"
TABLES=$(mysql -u root -pnewstart -D itqan_platform -e "SHOW TABLES LIKE 'ch_%' OR SHOW TABLES LIKE 'chat_group%';" 2>&1 | grep -v "Warning" | tail -n +2)

if [ -z "$TABLES" ]; then
    echo "   ✅ All Chatify tables removed"
else
    echo "   ❌ Some Chatify tables still exist:"
    echo "$TABLES" | sed 's/^/      /'
    ERRORS=$((ERRORS + 1))
fi

# 6. Check config
echo ""
echo "6️⃣  Configuration:"
if [ -f "config/chatify.php" ]; then
    echo "   ❌ config/chatify.php still exists"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ config/chatify.php removed"
fi

# 7. Check service providers
echo ""
echo "7️⃣  Service Providers:"
if grep -q "ChatifySubdomainServiceProvider" bootstrap/providers.php; then
    echo "   ❌ ChatifySubdomainServiceProvider still in bootstrap/providers.php"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ ChatifySubdomainServiceProvider removed from bootstrap"
fi

# 8. Check WireChat is active
echo ""
echo "8️⃣  WireChat Status:"
if [ -f "public/js/wirechat-realtime.js" ]; then
    echo "   ✅ wirechat-realtime.js exists"
else
    echo "   ❌ wirechat-realtime.js not found"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "wirechat-realtime.js" resources/views/chat/wirechat-content.blade.php; then
    echo "   ✅ wirechat-realtime.js loaded in view"
else
    echo "   ❌ wirechat-realtime.js not loaded in view"
    ERRORS=$((ERRORS + 1))
fi

# Summary
echo ""
echo "==========================================="
if [ $ERRORS -eq 0 ]; then
    echo "✅ Chatify Completely Removed!"
    echo "✅ WireChat is Active and Ready!"
else
    echo "⚠️  Found $ERRORS issue(s)"
    echo "   Some Chatify remnants remain"
fi
echo ""
