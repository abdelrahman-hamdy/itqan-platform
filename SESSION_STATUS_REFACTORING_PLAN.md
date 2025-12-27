# SessionStatus Enum Refactoring Plan

## Overview
This document outlines the comprehensive refactoring needed to replace SessionStatus string literals with proper enum usage across the codebase.

## SessionStatus Enum Values
- `UNSCHEDULED = 'unscheduled'`
- `SCHEDULED = 'scheduled'`
- `READY = 'ready'`
- `ONGOING = 'ongoing'`
- `COMPLETED = 'completed'`
- `CANCELLED = 'cancelled'`
- `ABSENT = 'absent'`

## Refactoring Patterns

### 1. Import Statement
Add to top of file after namespace:
```php
use App\Enums\SessionStatus;
```

### 2. Database Queries
**Before:**
```php
->where('status', 'completed')
->whereIn('status', ['scheduled', 'completed'])
```

**After:**
```php
->where('status', SessionStatus::COMPLETED->value)
->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::COMPLETED->value])
```

### 3. Enum Comparisons
**Before:**
```php
$session->status === 'completed'
$status == 'scheduled'
```

**After:**
```php
$session->status === SessionStatus::COMPLETED
$status == SessionStatus::SCHEDULED
```

### 4. Array Assignments
**Before:**
```php
'status' => 'scheduled'
```

**After:**
```php
'status' => SessionStatus::SCHEDULED
```

### 5. Collection Filtering
**Before:**
```php
$sessions->where('status', 'completed')
```

**After:**
```php
$sessions->where('status', SessionStatus::COMPLETED->value)
```

## Files Requiring Refactoring

### High Priority Controllers (84 files)
1. `app/Http/Controllers/QuranGroupCircleScheduleController.php` - ✅ COMPLETED
2. `app/Http/Controllers/ParentReportController.php` - ✅ PARTIALLY COMPLETED
3. `app/Http/Controllers/ParentProfileController.php`
4. `app/Http/Controllers/ParentCalendarController.php`
5. `app/Http/Controllers/AcademicTeacherController.php`
6. `app/Http/Controllers/UnifiedInteractiveCourseController.php`
7. `app/Http/Controllers/UnifiedQuranTeacherController.php`
8. `app/Http/Controllers/AcademicIndividualLessonController.php`
9. `app/Http/Controllers/QuranSessionController.php`
10. `app/Http/Controllers/TeacherProfileController.php`
...and 74 more controller files

### High Priority Services (71 files)
1. `app/Services/SessionManagementService.php`
2. `app/Services/ParentDashboardService.php`
3. `app/Services/ParentDataService.php`
4. `app/Services/QuranSessionSchedulingService.php`
5. `app/Services/AcademicSessionSchedulingService.php`
6. `app/Services/UnifiedSessionStatusService.php`
7. `app/Services/StudentStatisticsService.php`
8. `app/Services/CalendarService.php`
9. `app/Services/AutoMeetingCreationService.php`
10. `app/Services/RecordingService.php`
...and 61 more service files

### Livewire Components (4 files)
1. `app/Livewire/Student/AttendanceStatus.php`
2. `app/Livewire/IssueCertificateModal.php`
3. `app/Livewire/ReviewForm.php`
4. `app/Livewire/AcademySelector.php`

### Routes
1. `routes/web.php`

## Automated vs Manual Refactoring

### Recommended Approach
Given the scope (150+ files), a semi-automated approach is recommended:

1. **Phase 1: Automated Pattern Replacement**
   - Use IDE find/replace with regex for simple patterns
   - Focus on straightforward cases like `->where('status', 'value')`

2. **Phase 2: Manual Review**
   - Review complex cases with context
   - Handle edge cases (e.g., status in comments, strings, etc.)
   - Ensure no false positives

3. **Phase 3: Testing**
   - Run full test suite
   - Manual testing of critical flows
   - Check for any breaking changes

## Common Pitfalls to Avoid

1. **Don't confuse with other status fields:**
   - `attendance_status` - Different enum/string
   - `enrollment_status` - Different enum/string
   - `payment_status` - Different context

2. **Context-sensitive replacements:**
   - Comments/strings containing these words should NOT be changed
   - Status labels for display may need different treatment

3. **Mixed enum/string contexts:**
   - Some contexts require `->value`, others don't
   - Be careful with enum comparisons vs database queries

## Search Patterns for Finding Occurrences

```bash
# Find all status string literals (excluding comments)
grep -r "'scheduled'" app/Http/Controllers app/Services app/Livewire routes/web.php --include="*.php" | grep -v "//"

# Find whereIn with status arrays
grep -r "whereIn('status'" app/ --include="*.php"

# Find status comparisons
grep -r "->status ===" app/ --include="*.php"
grep -r "\$status ===" app/ --include="*.php"
```

## Testing Checklist

After refactoring, test these critical flows:

- [ ] Session creation (all types)
- [ ] Session status transitions
- [ ] Session filtering and queries
- [ ] Calendar display
- [ ] Reports generation
- [ ] Parent dashboard
- [ ] Teacher dashboard
- [ ] Student dashboard
- [ ] Attendance tracking
- [ ] Meeting management

## Progress Tracking

### Completed
- [x] QuranGroupCircleScheduleController.php
- [x] ParentReportController.php (partial)

### In Progress
- [ ] Remaining controllers
- [ ] All services
- [ ] Livewire components
- [ ] Routes

### Estimated Effort
- Total files: ~160
- Estimated time: 8-12 hours for complete refactoring
- Recommended: Batch processing with careful review

## Notes
- This is a breaking change if not done carefully
- All occurrences must be updated together
- Consider creating a PR for easier review
- Run tests after each batch of changes
