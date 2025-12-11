# Tests Directory

Test scripts and debugging tools for the Itqan Platform.

---

## 📁 Structure

### `/integration` (Integration Tests)

End-to-end tests for complete features:

- **`test-recording-integration.php`** ⭐ Recording feature integration test
  - Tests RecordingService, SessionRecording model, routes, controllers
  - Run: `php tests/integration/test-recording-integration.php`
  - **Result**: All 9 tests passed ✅

- **`test-cron-jobs.sh`** - Cron job/scheduler testing
  - Tests scheduled tasks and jobs

**Usage**:
```bash
# Test recording integration
php tests/integration/test-recording-integration.php

# Test cron jobs
./tests/integration/test-cron-jobs.sh
```

---

### `/debug` (Debugging Tools)

Diagnostic scripts for troubleshooting production issues:

- **`diagnose-attendance.php`** - Attendance system diagnostics
  - Checks attendance tracking, webhooks, real-time updates
  - Usage: `php tests/debug/diagnose-attendance.php`

- **`diagnose-chat.php`** - Chat system diagnostics
  - Checks WireChat configuration, database, permissions
  - Usage: `php tests/debug/diagnose-chat.php`

- **`debug-interactive-course-access.php`** - Course access debugging
  - Debugs enrollment and permission issues
  - Usage: `php tests/debug/debug-interactive-course-access.php`

**When to Use**:
- Production issues with attendance not recording
- Chat messages not appearing
- Students can't access courses

---

### `/Unit` (PHPUnit Tests)

Laravel PHPUnit tests (standard Laravel test directory):

**Middleware Tests**:
- `MaintenanceModeTest.php` - Tests maintenance mode feature

**Service Tests** (`/Unit/Services`):
- `SessionStatusServiceTest.php` - Tests session lifecycle management
- `NotificationServiceTest.php` - Tests notification dispatch system
- `CalendarServiceTest.php` - Tests calendar and scheduling

**Policy Tests** (`/Unit/Policies`):
- `SessionPolicyTest.php` - Tests session authorization rules

**Run PHPUnit tests**:
```bash
php artisan test
# or
php artisan test --filter MaintenanceModeTest
php artisan test --filter SessionStatusServiceTest
php artisan test --testsuite=Unit
```

---

## 🎯 Common Testing Scenarios

### Test Recording Feature
```bash
php tests/integration/test-recording-integration.php
```

**Expected Output**:
```
✅ Test 1: InteractiveCourseSession implements RecordingCapable
✅ Test 2: HasRecording trait integration
✅ Test 3: RecordingService methods
...
✅ ALL TESTS PASSED
```

### Debug Attendance Issues
```bash
php tests/debug/diagnose-attendance.php
```

**What it checks**:
- Database connection
- Attendance records
- Webhook configuration
- Real-time broadcasting

### Debug Chat Issues
```bash
php tests/debug/diagnose-chat.php
```

**What it checks**:
- WireChat installation
- Database tables
- Chat permissions
- Matrix server connection

---

## 📊 Test Coverage

### Integration Tests
- ✅ Recording feature (complete)
- ✅ Cron jobs/scheduler
- ⏳ Attendance system (partial - debug tools available)
- ⏳ Chat system (partial - debug tools available)

### Debug Tools
- ✅ Attendance diagnostics
- ✅ Chat diagnostics
- ✅ Course access debugging

---

## 🔧 Adding New Tests

### Integration Test Template
```php
#!/usr/bin/env php
<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

echo "=== Feature Integration Test ===\n\n";

// Test 1: Check component exists
echo "Test 1: Component exists\n";
$exists = class_exists('App\Models\YourModel');
echo $exists ? "✅ PASS\n" : "❌ FAIL\n";

// Add more tests...

echo "\n=== TEST SUMMARY ===\n";
echo $allPassed ? "✅ ALL TESTS PASSED\n" : "❌ SOME TESTS FAILED\n";
```

### Debug Tool Template
```php
#!/usr/bin/env php
<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

echo "=== Feature Diagnostics ===\n\n";

// Check 1: Database
echo "1. Checking database...\n";
try {
    $count = \App\Models\YourModel::count();
    echo "✅ Found {$count} records\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
}

// Add more checks...
```

---

## 📝 Notes

- Integration tests are standalone scripts (don't require PHPUnit)
- Debug tools are safe to run on production (read-only)
- All scripts bootstrap Laravel for database access
- Check script output for detailed error messages

---

## 🔗 Related

- **Main Tests**: `/tests/Feature/`, `/tests/Unit/` (PHPUnit)
- **Scripts**: `/scripts/` directory
- **Documentation**: `/docs/` directory
