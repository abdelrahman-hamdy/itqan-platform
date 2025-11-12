#!/bin/bash

echo "🧪 Testing WireChat Message Flow"
echo "================================="
echo ""

# Clear log
> storage/logs/laravel.log

echo "✅ Cleared Laravel log"
echo ""

# Run the PHP test script
php test-wirechat-message.php

echo ""
echo "📋 Checking logs..."
echo "──────────────────────────────────────"
echo ""

sleep 1

# Show broadcast logs
grep -E "\[WireChat|MessageCreated|📡|🔔|📺|✅|❌|🚀|🎉|🔧" storage/logs/laravel.log || echo "❌ No broadcast logs found"

echo ""
echo "──────────────────────────────────────"
echo ""
echo "✅ Test complete!"
echo ""
