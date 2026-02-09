#!/bin/bash

# Script to set up the test database for unit tests
# This project uses schema dumps instead of migrations

# Load from .env if available
if [ -f .env ]; then
    export $(grep -v '^#' .env | grep -v '^\s*$' | xargs)
fi
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-itqan_platform}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}
MYSQL_SOCKET=${MYSQL_SOCKET:-/tmp/mysql.sock}
TEST_DB="${DB_DATABASE}_test"

# Build mysql auth arguments
MYSQL_AUTH="--socket=${MYSQL_SOCKET} -u ${DB_USERNAME}"
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_AUTH="${MYSQL_AUTH} -p${DB_PASSWORD}"
fi

echo "Setting up test database..."

# Drop and recreate test database
mysql $MYSQL_AUTH << MYSQL_EOF
DROP DATABASE IF EXISTS ${TEST_DB};
CREATE DATABASE ${TEST_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ${TEST_DB};
SET FOREIGN_KEY_CHECKS=0;
MYSQL_EOF

# Load schema with FK checks disabled and single transaction
mysqldump $MYSQL_AUTH --no-data --skip-triggers --skip-add-locks --set-gtid-purged=OFF --single-transaction ${DB_DATABASE} 2>/dev/null | mysql $MYSQL_AUTH ${TEST_DB}

# Re-enable FK checks
mysql $MYSQL_AUTH ${TEST_DB} -e "SET FOREIGN_KEY_CHECKS=1;"

# Verify tables were created
TABLE_COUNT=$(mysql $MYSQL_AUTH ${TEST_DB} -e "SHOW TABLES;" 2>/dev/null | wc -l)

if [ "$TABLE_COUNT" -gt "50" ]; then
  echo "✓ Test database set up successfully ($TABLE_COUNT tables)"
  exit 0
else
  echo "✗ Failed to set up test database (only $TABLE_COUNT tables)"
  exit 1
fi
