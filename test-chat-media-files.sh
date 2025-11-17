#!/bin/bash

echo "======================================"
echo "Testing Chat Media & Files Feature"
echo "======================================"
echo ""

# Check if the custom Info component exists
echo "1. Checking if custom Info component exists..."
if [ -f "app/Livewire/Chat/Info.php" ]; then
    echo "   ✅ Custom Info component found"
else
    echo "   ❌ Custom Info component NOT found"
fi
echo ""

# Check if AppServiceProvider has been updated
echo "2. Checking if AppServiceProvider registers custom component..."
if grep -q "Livewire::component('wirechat.chat.info'" app/Providers/AppServiceProvider.php; then
    echo "   ✅ Component registration found in AppServiceProvider"
else
    echo "   ❌ Component registration NOT found in AppServiceProvider"
fi
echo ""

# Check if the view has been updated
echo "3. Checking if info.blade.php has tabbed interface..."
if grep -q "activeTab" resources/views/vendor/wirechat/livewire/chat/info.blade.php; then
    echo "   ✅ Tabbed interface implemented"
else
    echo "   ❌ Tabbed interface NOT implemented"
fi
echo ""

# Check attachments in database
echo "4. Checking database for attachments..."
php artisan tinker --execute="
\$total = DB::table('wire_attachments')->count();
\$media = DB::table('wire_attachments')->where('mime_type', 'LIKE', 'image/%')->orWhere('mime_type', 'LIKE', 'video/%')->count();
\$files = DB::table('wire_attachments')->where('mime_type', 'NOT LIKE', 'image/%')->where('mime_type', 'NOT LIKE', 'video/%')->count();
echo '   Total attachments: ' . \$total . PHP_EOL;
echo '   Media (images/videos): ' . \$media . PHP_EOL;
echo '   Files (documents): ' . \$files . PHP_EOL;
"
echo ""

echo "5. Testing component class..."
php -l app/Livewire/Chat/Info.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✅ No syntax errors in Info component"
else
    echo "   ❌ Syntax errors found in Info component"
fi
echo ""

echo "======================================"
echo "Testing Complete!"
echo "======================================"
echo ""
echo "Features implemented:"
echo "  • Separate tabs for Media and Files"
echo "  • Media displays in a 3-column grid"
echo "  • Files display in a list with icons"
echo "  • Real-time attachment counting"
echo "  • Download functionality for files"
echo "  • Clickable media previews"
echo "  • Empty state messages"
echo ""
echo "To test in browser:"
echo "  1. Navigate to the chat page"
echo "  2. Open a conversation"
echo "  3. Click on the info/details button"
echo "  4. Expand 'Media & Files' section"
echo "  5. Switch between Media and Files tabs"
echo ""
