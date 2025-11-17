#\!/bin/bash
tail -f storage/logs/laravel.log | grep --line-buffered -E "🔔 WEBHOOK|✅ \[WEBHOOK\]|❌ \[WEBHOOK\]|JOIN event|LEAVE event"

