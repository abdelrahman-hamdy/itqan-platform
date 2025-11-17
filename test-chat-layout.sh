#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Testing WireChat Layout & Functionality${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# 1. Check if services are running
echo -e "${YELLOW}1. Checking services...${NC}"
if pgrep -f "reverb:start" > /dev/null; then
    echo -e "   ${GREEN}✓ Reverb is running${NC}"
else
    echo -e "   ${RED}✗ Reverb is not running${NC}"
fi

if pgrep -f "queue:work" > /dev/null; then
    echo -e "   ${GREEN}✓ Queue worker is running${NC}"
else
    echo -e "   ${RED}✗ Queue worker is not running${NC}"
fi
echo ""

# 2. Check if view files exist
echo -e "${YELLOW}2. Checking view files...${NC}"
VIEWS=(
    "resources/views/vendor/wirechat/layouts/app.blade.php"
    "resources/views/vendor/wirechat/livewire/pages/chat.blade.php"
    "resources/views/vendor/wirechat/livewire/pages/chats.blade.php"
    "resources/views/vendor/wirechat/livewire/chat/chat.blade.php"
    "resources/views/vendor/wirechat/livewire/chats/chats.blade.php"
)

for view in "${VIEWS[@]}"; do
    if [ -f "$view" ]; then
        echo -e "   ${GREEN}✓ $view exists${NC}"
    else
        echo -e "   ${RED}✗ $view missing${NC}"
    fi
done
echo ""

# 3. Check navigation components
echo -e "${YELLOW}3. Checking navigation components...${NC}"
NAV_COMPONENTS=(
    "resources/views/components/navigation/student-nav.blade.php"
    "resources/views/components/navigation/teacher-nav.blade.php"
)

for nav in "${NAV_COMPONENTS[@]}"; do
    if [ -f "$nav" ]; then
        echo -e "   ${GREEN}✓ $nav exists${NC}"
    else
        echo -e "   ${RED}✗ $nav missing${NC}"
    fi
done
echo ""

# 4. Check build status
echo -e "${YELLOW}4. Checking asset build...${NC}"
if [ -f "public/build/manifest.json" ]; then
    MANIFEST_TIME=$(stat -f %m public/build/manifest.json 2>/dev/null || stat -c %Y public/build/manifest.json 2>/dev/null)
    CURRENT_TIME=$(date +%s)
    TIME_DIFF=$((CURRENT_TIME - MANIFEST_TIME))

    if [ $TIME_DIFF -lt 300 ]; then # Built within last 5 minutes
        echo -e "   ${GREEN}✓ Assets recently built ($(($TIME_DIFF / 60)) minutes ago)${NC}"
    else
        echo -e "   ${YELLOW}⚠ Assets built $(($TIME_DIFF / 60)) minutes ago${NC}"
    fi
else
    echo -e "   ${RED}✗ Build manifest not found${NC}"
fi
echo ""

# 5. Test WebSocket connection
echo -e "${YELLOW}5. Testing WebSocket connection...${NC}"
if curl -k -s https://localhost:8085 > /dev/null 2>&1; then
    echo -e "   ${GREEN}✓ Reverb server is accessible${NC}"
else
    echo -e "   ${RED}✗ Cannot reach Reverb server${NC}"
fi
echo ""

# 6. Check PHP syntax of key files
echo -e "${YELLOW}6. Checking PHP syntax...${NC}"
PHP_FILES=(
    "app/Livewire/WireChat/Chat.php"
    "app/Livewire/WireChat/Chats.php"
)

for file in "${PHP_FILES[@]}"; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo -e "   ${GREEN}✓ $file syntax OK${NC}"
        else
            echo -e "   ${RED}✗ $file has syntax errors${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠ $file not found (using vendor default)${NC}"
    fi
done
echo ""

# 7. Display test URLs
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Test URLs${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""
echo -e "${GREEN}Student View:${NC}"
echo -e "  https://itqan-platform.test/chats"
echo -e "  (Login as a student user)"
echo ""
echo -e "${GREEN}Teacher View:${NC}"
echo -e "  https://itqan-platform.test/chats"
echo -e "  (Login as a teacher user)"
echo ""
echo -e "${BLUE}Layout Tests to Perform:${NC}"
echo -e "  1. ✓ Top navigation bar is visible"
echo -e "  2. ✓ Chat container fits within viewport"
echo -e "  3. ✓ Sidebar is beside main chat area (desktop)"
echo -e "  4. ✓ Sidebar hides on mobile view"
echo -e "  5. ✓ Messages scroll properly"
echo -e "  6. ✓ Real-time messages appear instantly"
echo -e "  7. ✓ File upload works"
echo -e "  8. ✓ Emoji picker functions"
echo ""
echo -e "${BLUE}================================================${NC}"