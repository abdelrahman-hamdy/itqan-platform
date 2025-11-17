#!/bin/bash

# Simple Laravel Scheduler Runner for Development
# This keeps the scheduler running in your terminal
# Press Ctrl+C to stop

echo "================================================"
echo "Laravel Scheduler - Running Every Minute"
echo "================================================"
echo "Press Ctrl+C to stop"
echo ""

while true; do
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running scheduler..."
    php artisan schedule:run
    echo ""
    sleep 60
done
