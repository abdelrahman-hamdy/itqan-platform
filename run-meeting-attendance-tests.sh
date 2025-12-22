#!/bin/bash

# Script to run MeetingAttendanceServiceTest with proper database setup
# This ensures tests run serially to avoid parallel database conflicts

echo "===== Setting up test database ====="

# Kill any running test processes
pkill -f "pest.*itqan-platform" 2>/dev/null
sleep 2

# Drop and recreate test database
mysql -u root -e "DROP DATABASE IF EXISTS itqan_platform_test; CREATE DATABASE itqan_platform_test;" 2>/dev/null

# Copy schema from production database
echo "Copying database schema..."
mysqldump -u root itqan_platform --no-data --skip-comments > /tmp/test_schema.sql 2>/dev/null
mysql -u root itqan_platform_test < /tmp/test_schema.sql 2>/dev/null
rm /tmp/test_schema.sql

echo "===== Running MeetingAttendanceServiceTest ====="
echo ""

# Run the test file
# Using --order-by=default ensures tests run in the order they're defined
# This prevents random parallel execution
php vendor/bin/pest tests/Unit/Services/MeetingAttendanceServiceTest.php --order-by=default

echo ""
echo "===== Test run complete ====="
