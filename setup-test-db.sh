#!/bin/bash

# Script to set up the test database for unit tests
# This project uses schema dumps instead of migrations

echo "Setting up test database..."

# Drop and recreate test database
mysql --socket=/tmp/mysql.sock -u root << MYSQL_EOF
DROP DATABASE IF EXISTS itqan_platform_test;
CREATE DATABASE itqan_platform_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE itqan_platform_test;
SET FOREIGN_KEY_CHECKS=0;
MYSQL_EOF

# Load schema with FK checks disabled and single transaction
mysqldump --socket=/tmp/mysql.sock -u root --no-data --skip-triggers --skip-add-locks --set-gtid-purged=OFF --single-transaction itqan_platform 2>/dev/null | mysql --socket=/tmp/mysql.sock -u root itqan_platform_test

# Re-enable FK checks
mysql --socket=/tmp/mysql.sock -u root itqan_platform_test -e "SET FOREIGN_KEY_CHECKS=1;"

# Verify tables were created
TABLE_COUNT=$(mysql --socket=/tmp/mysql.sock -u root itqan_platform_test -e "SHOW TABLES;" 2>/dev/null | wc -l)

if [ "$TABLE_COUNT" -gt "50" ]; then
  echo "✓ Test database set up successfully ($TABLE_COUNT tables)"
  exit 0
else
  echo "✗ Failed to set up test database (only $TABLE_COUNT tables)"
  exit 1
fi
