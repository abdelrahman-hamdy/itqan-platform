#!/bin/bash

echo "🗑️  Removing Chatify Completely from Codebase"
echo "=============================================="
echo ""

# Backup first
echo "📦 Creating backup..."
BACKUP_DIR="chatify-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# 1. Remove Chatify JavaScript files
echo ""
echo "1️⃣  Removing Chatify JavaScript files..."
if [ -f "public/js/chat-system-reverb.js" ]; then
    mv public/js/chat-system-reverb.js "$BACKUP_DIR/"
    echo "   ✅ Removed public/js/chat-system-reverb.js"
fi

if [ -f "public/js/chatify.js" ]; then
    mv public/js/chatify.js "$BACKUP_DIR/"
    echo "   ✅ Removed public/js/chatify.js"
fi

# Remove compiled Chatify assets
rm -f public/js/chatify-*.js 2>/dev/null
rm -f public/css/chatify-*.css 2>/dev/null
echo "   ✅ Removed compiled Chatify assets"

# 2. Remove Chatify views (but keep WireChat views)
echo ""
echo "2️⃣  Removing old Chatify Blade files..."

# Remove specific Chatify view files (not WireChat)
OLD_CHAT_VIEWS=(
    "resources/views/chat/academic-teacher.blade.php"
    "resources/views/chat/academy-admin.blade.php"
    "resources/views/chat/admin.blade.php"
    "resources/views/chat/default.blade.php"
    "resources/views/chat/parent.blade.php"
    "resources/views/chat/student.blade.php"
    "resources/views/chat/supervisor.blade.php"
    "resources/views/chat/teacher.blade.php"
)

for file in "${OLD_CHAT_VIEWS[@]}"; do
    if [ -f "$file" ]; then
        mv "$file" "$BACKUP_DIR/" 2>/dev/null
        echo "   ✅ Removed $file"
    fi
done

# 3. Remove Chatify routes
echo ""
echo "3️⃣  Removing Chatify routes..."
if [ -d "routes/chatify" ]; then
    mv routes/chatify "$BACKUP_DIR/"
    echo "   ✅ Moved routes/chatify/ to backup"
fi

if [ -f "routes/api-chat.php" ]; then
    mv routes/api-chat.php "$BACKUP_DIR/"
    echo "   ✅ Removed routes/api-chat.php"
fi

# 4. Remove Chatify controllers
echo ""
echo "4️⃣  Removing Chatify controllers..."
if [ -d "app/Http/Controllers/vendor/Chatify" ]; then
    mv app/Http/Controllers/vendor/Chatify "$BACKUP_DIR/"
    echo "   ✅ Moved app/Http/Controllers/vendor/Chatify/ to backup"
fi

# 5. Remove Chatify models
echo ""
echo "5️⃣  Removing Chatify models..."
CHATIFY_MODELS=(
    "app/Models/ChMessage.php"
    "app/Models/ChFavorite.php"
    "app/Models/ChatGroup.php"
    "app/Models/ChatGroupMember.php"
)

for model in "${CHATIFY_MODELS[@]}"; do
    if [ -f "$model" ]; then
        mv "$model" "$BACKUP_DIR/" 2>/dev/null
        echo "   ✅ Removed $model"
    fi
done

# 6. Remove Chatify events (keep only WireChat events)
echo ""
echo "6️⃣  Removing Chatify-specific events..."
CHATIFY_EVENTS=(
    "app/Events/MessageSentEvent.php"
    "app/Events/MessageSent.php"
    "app/Events/MessageReadEvent.php"
    "app/Events/MessageDeliveredEvent.php"
    "app/Events/UserTypingEvent.php"
)

for event in "${CHATIFY_EVENTS[@]}"; do
    if [ -f "$event" ]; then
        mv "$event" "$BACKUP_DIR/" 2>/dev/null
        echo "   ✅ Removed $event"
    fi
done

# 7. Remove Chatify service providers
echo ""
echo "7️⃣  Removing Chatify service provider..."
if [ -f "app/Providers/ChatifySubdomainServiceProvider.php" ]; then
    mv app/Providers/ChatifySubdomainServiceProvider.php "$BACKUP_DIR/"
    echo "   ✅ Removed ChatifySubdomainServiceProvider"
fi

# 8. Remove Chatify assets
echo ""
echo "8️⃣  Removing Chatify public assets..."
if [ -d "public/vendor/chatify" ]; then
    mv public/vendor/chatify "$BACKUP_DIR/"
    echo "   ✅ Removed public/vendor/chatify/"
fi

# 9. Remove Chatify sounds
if [ -d "public/sounds/chat" ]; then
    mv public/sounds/chat "$BACKUP_DIR/"
    echo "   ✅ Removed public/sounds/chat/"
fi

# 10. Remove Chatify config
echo ""
echo "9️⃣  Removing Chatify configuration..."
if [ -f "config/chatify.php" ]; then
    mv config/chatify.php "$BACKUP_DIR/"
    echo "   ✅ Removed config/chatify.php"
fi

# 11. Update bootstrap/providers.php
echo ""
echo "🔟 Updating bootstrap/providers.php..."
if grep -q "ChatifySubdomainServiceProvider" bootstrap/providers.php; then
    sed -i.bak '/ChatifySubdomainServiceProvider/d' bootstrap/providers.php
    echo "   ✅ Removed ChatifySubdomainServiceProvider from providers"
fi

# 12. Clear caches
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "   ✅ Caches cleared"

# 13. Drop Chatify database tables
echo ""
echo "💾 Dropping Chatify database tables..."
mysql -u root -pnewstart -D itqan_platform << 'EOF' 2>&1 | grep -v "Warning"
DROP TABLE IF EXISTS ch_messages;
DROP TABLE IF EXISTS ch_favorites;
DROP TABLE IF EXISTS chat_groups;
DROP TABLE IF EXISTS chat_group_members;
EOF
echo "   ✅ Chatify tables dropped"

echo ""
echo "✅ Chatify Removal Complete!"
echo ""
echo "📦 Backup Location: $BACKUP_DIR/"
echo ""
echo "🎯 Next Steps:"
echo "   1. Check that WireChat is working"
echo "   2. If everything works, delete the backup:"
echo "      rm -rf $BACKUP_DIR"
echo ""
