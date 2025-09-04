#!/bin/bash
echo "=== MONITORING LOGS FOR SUBSCRIPTION ERRORS ==="
echo "Press Ctrl+C to stop monitoring"
echo ""

# Monitor logs and save to a separate file for easier reading
tail -f storage/logs/laravel.log | tee subscription_debug.log | grep -E "(SUBSCRIPTION|subscription|academic|ERROR|Failed)"
