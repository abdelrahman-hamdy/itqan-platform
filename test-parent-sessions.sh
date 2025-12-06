#!/bin/bash

# Test Parent Upcoming Sessions Debug Script
# This script helps view real-time logs for parent session debugging

echo "==================================================="
echo "Parent Upcoming Sessions Debug Tool"
echo "==================================================="
echo ""
echo "Instructions:"
echo "1. Open your browser and navigate to the parent profile page"
echo "2. Watch the logs below in real-time"
echo "3. Look for '[Parent Upcoming Sessions]' entries"
echo ""
echo "Press Ctrl+C to stop watching logs"
echo ""
echo "==================================================="
echo ""

# Clear Laravel log cache first
php artisan cache:clear > /dev/null 2>&1

# Watch logs in real-time, filtering for Parent Upcoming Sessions
php artisan pail --filter="Parent Upcoming Sessions"
