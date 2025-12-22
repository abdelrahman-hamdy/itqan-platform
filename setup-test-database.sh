#!/bin/bash

# Setup Test Database Script
# This script sets up the test database by copying schema from production

echo "Setting up test database..."

# Recreate test database
mysql -u root -e "DROP DATABASE IF EXISTS itqan_platform_test;"
mysql -u root -e "CREATE DATABASE itqan_platform_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Copy schema from production (without triggers to avoid deadlocks)
mysqldump -u root --no-data --skip-triggers itqan_platform > /tmp/test_schema.sql
mysql -u root itqan_platform_test < /tmp/test_schema.sql

# Clean up
rm /tmp/test_schema.sql

echo "Test database setup complete!"
echo ""
echo "You can now run tests with:"
echo "  php artisan test tests/Unit/Services/SessionManagementServiceTest.php"
echo ""
echo "Or run with pest directly (better for serial execution):"
echo "  vendor/bin/pest tests/Unit/Services/SessionManagementServiceTest.php"
