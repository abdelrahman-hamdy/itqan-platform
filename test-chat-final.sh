#!/bin/bash

echo "=========================================="
echo "🚀 Final Chat Info Improvements Test"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counters
PASS=0
FAIL=0

# Function to test
test_item() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
        ((PASS++))
    else
        echo -e "${RED}❌ $1${NC}"
        ((FAIL++))
    fi
}

echo "1️⃣  Checking Files..."
echo "---"

# Check if Info blade exists
[ -f "resources/views/vendor/wirechat/livewire/chat/info.blade.php" ]
test_item "Info blade view exists"

# Check if custom Info component exists
[ -f "app/Livewire/Chat/Info.php" ]
test_item "Custom Info component exists"

# Check blade syntax
php -l resources/views/vendor/wirechat/livewire/chat/info.blade.php > /dev/null 2>&1
test_item "Blade view has no syntax errors"

# Check component syntax
php -l app/Livewire/Chat/Info.php > /dev/null 2>&1
test_item "Component has no syntax errors"

echo ""
echo "2️⃣  Checking Arabic Translations..."
echo "---"

# Check if Arabic translation files exist
[ -f "lang/ar/vendor/wirechat/chat.php" ]
test_item "Arabic chat translations exist"

[ -f "lang/ar/vendor/wirechat/chats.php" ]
test_item "Arabic chats translations exist"

[ -f "lang/ar/vendor/wirechat/new.php" ]
test_item "Arabic new chat translations exist"

[ -f "lang/ar/vendor/wirechat/pages.php" ]
test_item "Arabic pages translations exist"

[ -f "lang/ar/vendor/wirechat/validation.php" ]
test_item "Arabic validation translations exist"

[ -f "lang/ar/vendor/wirechat/widgets.php" ]
test_item "Arabic widgets translations exist"

echo ""
echo "3️⃣  Checking Configuration..."
echo "---"

# Check if locale is set to Arabic
grep -q "'locale' => env('APP_LOCALE', 'ar')" config/app.php
test_item "App locale set to Arabic"

# Check if component is registered
grep -q "Livewire::component('wirechat.chat.info'" app/Providers/AppServiceProvider.php
test_item "Custom component registered"

echo ""
echo "4️⃣  Checking Content..."
echo "---"

# Check if tabs are implemented
grep -q "activeTab" resources/views/vendor/wirechat/livewire/chat/info.blade.php
test_item "Tab interface implemented"

# Check if Arabic text is present
grep -q "الوسائط" resources/views/vendor/wirechat/livewire/chat/info.blade.php
test_item "Arabic text in view"

# Check if delete button is inline
grep -q "inline-flex" resources/views/vendor/wirechat/livewire/chat/info.blade.php
test_item "Delete button is inline-block"

# Check if media loading is lazy
grep -q 'loading="lazy"' resources/views/vendor/wirechat/livewire/chat/info.blade.php
test_item "Images use lazy loading"

echo ""
echo "5️⃣  Checking Database..."
echo "---"

# Check attachments
php artisan tinker --execute="
\$total = DB::table('wire_attachments')->count();
\$media = DB::table('wire_attachments')->where('mime_type', 'LIKE', 'image/%')->orWhere('mime_type', 'LIKE', 'video/%')->count();
\$files = DB::table('wire_attachments')->where('mime_type', 'NOT LIKE', 'image/%')->where('mime_type', 'NOT LIKE', 'video/%')->count();
echo '   📊 Database Statistics:' . PHP_EOL;
echo '   Total attachments: ' . \$total . PHP_EOL;
echo '   Media items: ' . \$media . PHP_EOL;
echo '   File items: ' . \$files . PHP_EOL;
" 2>&1 | grep -v "^$"

echo ""
echo "6️⃣  Design Features..."
echo "---"

# Check modern design elements
grep -q "rounded-xl" resources/views/vendor/wirechat/livewire/chat/info.blade.php && echo -e "${GREEN}✅ Rounded corners${NC}" || echo -e "${RED}❌ Rounded corners${NC}"
grep -q "hover:" resources/views/vendor/wirechat/livewire/chat/info.blade.php && echo -e "${GREEN}✅ Hover effects${NC}" || echo -e "${RED}❌ Hover effects${NC}"
grep -q "transition" resources/views/vendor/wirechat/livewire/chat/info.blade.php && echo -e "${GREEN}✅ Smooth transitions${NC}" || echo -e "${RED}❌ Smooth transitions${NC}"
grep -q "dark:" resources/views/vendor/wirechat/livewire/chat/info.blade.php && echo -e "${GREEN}✅ Dark mode support${NC}" || echo -e "${RED}❌ Dark mode support${NC}"

echo ""
echo "=========================================="
echo "📊 Test Results"
echo "=========================================="
echo -e "${GREEN}Passed: $PASS${NC}"
echo -e "${RED}Failed: $FAIL${NC}"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed! Implementation is complete.${NC}"
else
    echo -e "${YELLOW}⚠️  Some tests failed. Please review.${NC}"
fi

echo ""
echo "=========================================="
echo "🌐 Testing in Browser"
echo "=========================================="
echo ""
echo "To test the implementation in your browser:"
echo ""
echo "1. Navigate to: http://yourdomain.test/chats"
echo "2. Open any conversation"
echo "3. Click the info button (usually top-right)"
echo "4. Expand 'الوسائط والملفات' section"
echo "5. Test switching between tabs"
echo "6. Verify media and files display correctly"
echo "7. Check delete button styling"
echo "8. Verify all text is in Arabic"
echo ""
echo "=========================================="
echo "📝 Documentation"
echo "=========================================="
echo ""
echo "See CHAT_INFO_IMPROVEMENTS_FINAL.md for:"
echo "  • Complete feature list"
echo "  • Technical details"
echo "  • Testing checklist"
echo "  • Future enhancements"
echo ""
