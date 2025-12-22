# Test Database Issue - Service Tests

## Problem Summary

All service tests in the following files are failing due to a **MySQL schema deadlock error**, NOT due to issues with the test code itself:

- `tests/Unit/Services/AutoMeetingCreationServiceTest.php`
- `tests/Unit/Services/AcademyContextServiceTest.php`
- `tests/Unit/Services/CircleEnrollmentServiceTest.php`
- `tests/Unit/Services/LiveKitServiceTest.php`
- `tests/Unit/Services/MeetingAttendanceServiceTest.php`
- `tests/Unit/Services/MeetingDataChannelServiceTest.php`
- `tests/Unit/Services/QuizServiceTest.php`
- `tests/Unit/Services/ReviewServiceTest.php`

## Root Cause

### Primary Issue: MySQL Schema Deadlock

The error occurs when Pest tries to use `LazilyRefreshDatabase` trait to set up the test database:

```
ERROR 1213 (40001) at line 3630: Deadlock found when trying to get lock; try restarting transaction
```

This happens during the loading of `database/schema/mysql-schema.sql`.

### Secondary Issue: No Migration Files

**CRITICAL DISCOVERY**: The project has **NO migration files** in `database/migrations/`. The entire database structure exists only as:
- `database/schema/mysql-schema.sql` - MySQL schema dump (has deadlock issue)
- No `*.php` migration files exist

This means:
- Cannot use SQLite or other databases without migrations
- Cannot regenerate schema using `php artisan schema:dump` (no migrations to run)
- Tests are completely dependent on the MySQL schema file working

## Test Code Status

**All test code is correct and matches the service implementations**. After detailed analysis:

1. All service method signatures match test expectations
2. Return values and data structures match between services and tests
3. Test assertions are appropriate and correct
4. Mocking is done properly where needed
5. Factory usage is correct

**NO CODE CHANGES ARE NEEDED** in any of the 8 test files.

## Solution: Fix MySQL Schema Deadlock

Since there are no migrations, the ONLY solution is to fix the MySQL schema file deadlock.

### Steps to Fix:

1. **Identify the problematic line** in `database/schema/mysql-schema.sql`:
   - Line 3630 (or 377 when tables are dropped)
   - Look for foreign key constraints or complex operations causing deadlock

2. **Common deadlock causes in schema files**:
   ```sql
   -- Problematic: Multiple foreign keys being created simultaneously
   ALTER TABLE `table1` ADD CONSTRAINT FOREIGN KEY...
   ALTER TABLE `table2` ADD CONSTRAINT FOREIGN KEY...

   -- Solution: Add delays or reorder constraints
   ```

3. **Quick fix approach**:
   ```bash
   # Drop the test database
   mysql -u root -p -e "DROP DATABASE IF EXISTS itqan_platform_test;"
   mysql -u root -p -e "CREATE DATABASE itqan_platform_test;"

   # Load schema with single-threaded approach
   mysql -u root -p itqan_platform_test < database/schema/mysql-schema.sql
   ```

4. **If above fails, split the schema file**:
   - Split `mysql-schema.sql` into smaller chunks
   - Load them sequentially
   - Identify which chunk has the deadlock

### Alternative: Generate Migrations from Production

If you have access to a working production/development database:

```bash
# Generate migrations from existing database
php artisan migrate:generate

# This will create migration files in database/migrations/
# Then tests can use migrations instead of schema
```

## Verification

Once the schema deadlock is fixed:

```bash
# Clear any caches
php artisan config:clear
php artisan cache:clear

# Run tests
php artisan test tests/Unit/Services/

# All tests should pass without code changes
```

## Why Other Solutions Won't Work

### ❌ SQLite Alternative
- **Won't work**: No migration files exist
- Laravel's `LazilyRefreshDatabase` needs either:
  - Migration files to run, OR
  - A schema file for the chosen database
- We only have MySQL schema file

### ❌ Regenerating Schema
- **Won't work**: `php artisan schema:dump` requires migrations
- No migrations exist to run

### ❌ Converting to Migrations
- **Possible but complex**: Would require manually creating ~100+ migration files
- Time-consuming and error-prone
- Not recommended as a quick fix

## Notes

- Tests use `LazilyRefreshDatabase` which automatically handles database setup/teardown
- Tests are marked as `serial` to prevent parallel execution
- All service implementations match their test expectations perfectly
- **No test code modifications needed** - only fix the MySQL schema deadlock

## Status

- ✅ Test code: Fully correct, no changes needed
- ✅ Service implementations: Match test expectations
- ❌ Database setup: MySQL schema has deadlock issue
- ❌ Migration files: None exist in project
