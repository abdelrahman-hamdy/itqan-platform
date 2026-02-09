#!/bin/bash

# Set up all test databases (main and parallel workers)

# Load from .env if available
if [ -f .env ]; then
    export $(grep -v '^#' .env | grep -v '^\s*$' | xargs)
fi
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-itqan_platform}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

# Build mysql auth arguments
MYSQL_AUTH="-u ${DB_USERNAME} -h ${DB_HOST} -P ${DB_PORT}"
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_AUTH="${MYSQL_AUTH} -p${DB_PASSWORD}"
fi

echo "Setting up all test databases..."

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="${SCRIPT_DIR}/database/schema/mysql-schema.sql"

# Array of test database names
TEST_DBS=("${DB_DATABASE}_test" "${DB_DATABASE}_test_test_1" "${DB_DATABASE}_test_test_2" "${DB_DATABASE}_test_test_3" "${DB_DATABASE}_test_test_4")

for TEST_DB_NAME in "${TEST_DBS[@]}"; do
    echo "Setting up $TEST_DB_NAME..."

    # Drop and recreate
    mysql $MYSQL_AUTH -e "DROP DATABASE IF EXISTS $TEST_DB_NAME;"
    mysql $MYSQL_AUTH -e "CREATE DATABASE $TEST_DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    # Load schema
    mysql $MYSQL_AUTH $TEST_DB_NAME < "$SCHEMA_FILE" 2>&1 | grep -v "Warning" || true

    # Check table count
    TABLE_COUNT=$(mysql $MYSQL_AUTH $TEST_DB_NAME -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TEST_DB_NAME';" 2>/dev/null | tail -1)

    if [ "$TABLE_COUNT" -gt "50" ]; then
        echo "✓ $TEST_DB_NAME set up successfully ($TABLE_COUNT tables)"
    else
        echo "✗ $TEST_DB_NAME setup may have issues (only $TABLE_COUNT tables)"
    fi
done

echo "All test databases ready!"
