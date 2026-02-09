#!/bin/bash

# Setup Test Database Script
# This script sets up the test database by copying schema from production

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

echo "Setting up test database..."

# Recreate test database
mysql $MYSQL_AUTH -e "DROP DATABASE IF EXISTS ${TEST_DB};"
mysql $MYSQL_AUTH -e "CREATE DATABASE ${TEST_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Copy schema from production (without triggers to avoid deadlocks)
mysqldump $MYSQL_AUTH --no-data --skip-triggers ${DB_DATABASE} > /tmp/test_schema.sql
mysql $MYSQL_AUTH ${TEST_DB} < /tmp/test_schema.sql

# Clean up
rm /tmp/test_schema.sql

echo "Test database setup complete!"
echo ""
echo "You can now run tests with:"
echo "  php artisan test tests/Unit/Services/SessionManagementServiceTest.php"
echo ""
echo "Or run with pest directly (better for serial execution):"
echo "  vendor/bin/pest tests/Unit/Services/SessionManagementServiceTest.php"
