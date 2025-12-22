#!/bin/bash

# Set up all test databases (main and parallel workers)

echo "Setting up all test databases..."

SCHEMA_FILE="/Users/abdelrahmanhamdy/web/itqan-platform/database/schema/mysql-schema.sql"

# Array of test database names
TEST_DBS=("itqan_platform_test" "itqan_platform_test_test_1" "itqan_platform_test_test_2" "itqan_platform_test_test_3" "itqan_platform_test_test_4")

for DB_NAME in "${TEST_DBS[@]}"; do
    echo "Setting up $DB_NAME..."

    # Drop and recreate
    mysql -u root -e "DROP DATABASE IF EXISTS $DB_NAME;"
    mysql -u root -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    # Load schema
    mysql -u root $DB_NAME < "$SCHEMA_FILE" 2>&1 | grep -v "Warning" || true

    # Check table count
    TABLE_COUNT=$(mysql -u root $DB_NAME -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null | tail -1)

    if [ "$TABLE_COUNT" -gt "50" ]; then
        echo "✓ $DB_NAME set up successfully ($TABLE_COUNT tables)"
    else
        echo "✗ $DB_NAME setup may have issues (only $TABLE_COUNT tables)"
    fi
done

echo "All test databases ready!"
