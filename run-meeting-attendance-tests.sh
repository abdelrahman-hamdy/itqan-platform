#!/bin/bash

# Script to run MeetingAttendanceServiceTest with proper database setup
# This ensures tests run serially to avoid parallel database conflicts

# Load from .env if available
if [ -f .env ]; then
    export $(grep -v '^#' .env | grep -v '^\s*$' | xargs)
fi
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-itqan_platform}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
TEST_DB="${DB_DATABASE}_test"

# Build mysql auth arguments
MYSQL_AUTH="-u ${DB_USERNAME} -h ${DB_HOST} -P ${DB_PORT}"
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_AUTH="${MYSQL_AUTH} -p${DB_PASSWORD}"
fi

echo "===== Setting up test database ====="

# Kill any running test processes
pkill -f "pest.*itqan-platform" 2>/dev/null
sleep 2

# Drop and recreate test database
mysql $MYSQL_AUTH -e "DROP DATABASE IF EXISTS ${TEST_DB}; CREATE DATABASE ${TEST_DB};" 2>/dev/null

# Copy schema from production database
echo "Copying database schema..."
mysqldump $MYSQL_AUTH ${DB_DATABASE} --no-data --skip-comments > /tmp/test_schema.sql 2>/dev/null
mysql $MYSQL_AUTH ${TEST_DB} < /tmp/test_schema.sql 2>/dev/null
rm /tmp/test_schema.sql

echo "===== Running MeetingAttendanceServiceTest ====="
echo ""

# Run the test file
# Using --order-by=default ensures tests run in the order they're defined
# This prevents random parallel execution
php vendor/bin/pest tests/Unit/Services/MeetingAttendanceServiceTest.php --order-by=default

echo ""
echo "===== Test run complete ====="
