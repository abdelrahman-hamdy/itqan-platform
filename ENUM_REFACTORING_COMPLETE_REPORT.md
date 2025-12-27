# Enum Refactoring Report - String Literals to Enum Constants

## Overview
This document provides a comprehensive refactoring guide to replace all string-based enum usage in Models with proper enum constants as per production requirements.

## Enum Classes Available
1. **AttendanceStatus** (`App\Enums\AttendanceStatus`)
   - `ATTENDED = 'attended'`
   - `LATE = 'late'`
   - `LEAVED = 'leaved'`
   - `ABSENT = 'absent'`

2. **SessionStatus** (`App\Enums\SessionStatus`)
   - `SCHEDULED = 'scheduled'`
   - `ONGOING = 'ongoing'`
   - `COMPLETED = 'completed'`
   - `CANCELLED = 'cancelled'`
   - `READY = 'ready'`
   - `ABSENT = 'absent'`
   - `UNSCHEDULED = 'unscheduled'`

3. **SubscriptionStatus** (`App\Enums\SubscriptionStatus`)
   - `PENDING = 'pending'`
   - `ACTIVE = 'active'`
   - `PAUSED = 'paused'`
   - `EXPIRED = 'expired'`
   - `CANCELLED = 'cancelled'`
   - `COMPLETED = 'completed'`
   - `REFUNDED = 'refunded'`

## Refactoring Patterns

### Pattern 1: Comparisons
```php
// OLD
if ($this->status === 'completed')
if ($this->attendance_status !== 'absent')

// NEW
if ($this->status === SessionStatus::COMPLETED)  // When casted to enum
if ($this->attendance_status === AttendanceStatus::ABSENT->value)  // When string
```

### Pattern 2: Assignments in Arrays
```php
// OLD
$this->update(['status' => 'completed']);
$this->update(['attendance_status' => 'attended']);

// NEW
$this->update(['status' => SessionStatus::COMPLETED]);
$this->update(['attendance_status' => AttendanceStatus::ATTENDED->value]);
```

### Pattern 3: Default Attributes
```php
// OLD
protected $attributes = [
    'status' => 'scheduled',
    'attendance_status' => 'absent',
];

// NEW
protected $attributes = [
    'status' => SessionStatus::SCHEDULED->value,
    'attendance_status' => AttendanceStatus::ABSENT->value,
];
```

### Pattern 4: Return Statements
```php
// OLD
return 'attended';
return 'completed';

// NEW
return AttendanceStatus::ATTENDED->value;
return SessionStatus::COMPLETED->value;
```

### Pattern 5: Scopes
```php
// OLD
public function scopeCompleted($query)
{
    return $query->where('status', 'completed');
}

// NEW
public function scopeCompleted($query)
{
    return $query->where('status', SessionStatus::COMPLETED);
}
```

### Pattern 6: Ternary Operators
```php
// OLD
$status = $isLate ? 'late' : 'attended';

// NEW
$status = $isLate ? AttendanceStatus::LATE->value : AttendanceStatus::ATTENDED->value;
```

## Files Requiring Refactoring

### Priority 1: Base Models (CRITICAL)
1. **BaseSessionAttendance.php**
   - Lines requiring changes:
     - Line 96: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
     - Line 335: `=== 'attended'` → `=== AttendanceStatus::ATTENDED->value`
     - Line 344: `? 'late' : 'attended'` → `? AttendanceStatus::LATE->value : AttendanceStatus::ATTENDED->value`
     - Line 406: `return 'absent'` → `return AttendanceStatus::ABSENT->value`
     - Line 414: `return 'absent'` → `return AttendanceStatus::ABSENT->value`
     - Line 422: `return 'late'` → `return AttendanceStatus::LATE->value`
     - Line 428: `return 'leaved'` → `return AttendanceStatus::LEAVED->value`
     - Line 431: `return 'attended'` → `return AttendanceStatus::ATTENDED->value`
     - Line 507: `=== 'ongoing'` → `=== SessionStatus::ONGOING`
     - Line 508: `=== 'absent'` → `=== AttendanceStatus::ABSENT->value`
     - Line 516: `=== 'attended'` → `=== AttendanceStatus::ATTENDED->value`
   - Required imports: `use App\Enums\AttendanceStatus;`, `use App\Enums\SessionStatus;`

2. **BaseSessionReport.php**
   - Lines requiring changes:
     - Line 161: `->where('attendance_status', 'attended')` → `AttendanceStatus::ATTENDED->value`
     - Line 166: `->where('attendance_status', 'absent')` → `AttendanceStatus::ABSENT->value`
     - Line 171: `->where('attendance_status', 'late')` → `AttendanceStatus::LATE->value`
     - Line 176: `->where('attendance_status', 'leaved')` → `AttendanceStatus::LEAVED->value`
   - Required imports: `use App\Enums\AttendanceStatus;`

3. **AcademicSession.php**
   - Lines requiring changes:
     - Line 98: `'status' => 'scheduled'` → `SessionStatus::SCHEDULED->value`
     - Line 101: `'attendance_status' => 'scheduled'` → Should be `AttendanceStatus::ABSENT->value` (scheduled is not valid attendance status)
     - Line 576: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
     - Line 626: `'attendance_status' => 'attended'` → `AttendanceStatus::ATTENDED->value`
     - Line 687: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
   - Required imports: Already has `use App\Enums\SessionStatus;`, needs `use App\Enums\AttendanceStatus;`

4. **QuranSession.php**
   - Multiple lines with string literals for `status` and `attendance_status`
   - Line 259: `->where('status', 'missed')` → Should use `SessionStatus::ABSENT` or custom status
   - Line 286: `->where('session_type', 'group')` → Session type, NOT an enum (OK as-is)
   - Line 380: `'attendance_status' => 'attended'` → `AttendanceStatus::ATTENDED->value`
     - Line 442: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
   - Line 471: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
   - Line 526: `'attendance_status' => $status` → `$status` needs to be enum value
   - Line 617: `->where('attendance_status', 'attended')` → `AttendanceStatus::ATTENDED->value`
   - Line 622: `->where('attendance_status', 'absent')` → `AttendanceStatus::ABSENT->value`
   - Line 824: `'attendance_status' => AttendanceStatus::ATTENDED` → Already correct!
   - Line 883: `'attendance_status' => AttendanceStatus::ABSENT` → Already correct!
   - Line 1063: `'status' => 'scheduled'` → `SessionStatus::SCHEDULED->value`
   - Required imports: Already has both imports

### Priority 2: Subscription Models
5. **QuranSubscription.php**
   - Line 711: `!== 'active'` → `!== SubscriptionStatus::ACTIVE->value`
   - Required imports: `use App\Enums\SubscriptionStatus;`

6. **AcademicSubscription.php**
   - Check for status string literals
   - Required imports: `use App\Enums\SubscriptionStatus;`

7. **BaseSubscription.php**
   - Check for status string literals
   - Required imports: `use App\Enums\SubscriptionStatus;`

8. **CourseSubscription.php**
   - Check for status string literals
   - Required imports: `use App\Enums\SubscriptionStatus;`

### Priority 3: Circle/Lesson Models
9. **QuranCircle.php**
   - Check for status string literals
   - Required imports: TBD based on findings

10. **QuranIndividualCircle.php**
    - Line 231: `=== 'pending'` → Needs investigation (circle status, not subscription)
    - Required imports: TBD

11. **AcademicIndividualLesson.php**
    - Line 183: `=== 'active'` → Lesson status, may need custom enum
    - Line 188: `=== 'completed'` → Lesson status, may need custom enum
    - Required imports: TBD

### Priority 4: Other Models
12. **MeetingAttendance.php**
    - Check for attendance_status literals
    - Required imports: `use App\Enums\AttendanceStatus;`

13. **SessionRequest.php**
    - Line 194: `=== 'expired'` → RequestStatus, not SubscriptionStatus
    - Required imports: TBD

14. **HomeworkSubmission.php**
    - Line 350: `=== 'late'` → submission_status, may need custom enum
    - Required imports: TBD

15. **InteractiveCourseSession.php**
    - Check for session status literals
    - Required imports: `use App\Enums\SessionStatus;`

16. **InteractiveCourseEnrollment.php**
    - Check for enrollment status literals
    - Required imports: TBD

17. **Payment.php**
    - Line 224: `=== 'completed' && === 'paid'` → Payment status, different from subscription
    - Line 229: `=== 'pending'` → Payment status
    - Required imports: May need PaymentStatus enum

18. **AcademicSessionReport.php**
    - Line 248: `=== 'completed'` → `SessionStatus::COMPLETED`
    - Required imports: `use App\Enums\SessionStatus;`

19. **CourseRecording.php**
    - Line 87: `=== 'completed'` → Recording status, may need custom enum
    - Required imports: TBD

20. **SessionRecording.php**
    - Line 145: `=== 'completed'` → Recording status, may need custom enum
    - Required imports: TBD

21. **TeacherPayout.php**
    - Line 141: `=== 'pending'` → Payout status, may need custom enum
    - Required imports: TBD

## Implementation Steps

### Step 1: Verify Enum Casts
Ensure all models properly cast enum fields:
```php
protected $casts = [
    'status' => SessionStatus::class,  // For session models
    'attendance_status' => AttendanceStatus::class,  // NOT recommended - use string
];
```

**IMPORTANT**: Most models use `attendance_status` as a string field, NOT casted to enum.
Therefore, always use `AttendanceStatus::ATTENDED->value` in comparisons.

### Step 2: Add Required Imports
Add imports at the top of each file:
```php
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\SubscriptionStatus;
```

### Step 3: Replace String Literals
Follow the patterns above to replace all string literals.

### Step 4: Test
Run comprehensive tests:
```bash
php artisan test
```

## Special Cases

### Case 1: Casted Enum Fields
When `status` is casted to `SessionStatus::class`:
```php
// The property is already an enum instance
if ($this->status === SessionStatus::COMPLETED) // Correct
if ($this->status->value === 'completed') // Also works but verbose
```

### Case 2: String Fields (Recommended)
When `attendance_status` is a string (NOT casted):
```php
// Always use ->value
if ($this->attendance_status === AttendanceStatus::ATTENDED->value) // Correct
if ($this->attendance_status === AttendanceStatus::ATTENDED) // WRONG - comparing string to enum
```

### Case 3: Match Statements
```php
// OLD
return match ($this->status) {
    'scheduled' => 'blue',
    'ongoing' => 'green',
    'completed' => 'gray',
};

// NEW (if casted to enum)
return match ($this->status) {
    SessionStatus::SCHEDULED => 'blue',
    SessionStatus::ONGOING => 'green',
    SessionStatus::COMPLETED => 'gray',
};

// NEW (if string field)
return match ($this->status) {
    SessionStatus::SCHEDULED->value => 'blue',
    SessionStatus::ONGOING->value => 'green',
    SessionStatus::COMPLETED->value => 'gray',
};
```

## Validation Checklist

- [ ] All `'attended'`, `'late'`, `'absent'`, `'leaved'` replaced with `AttendanceStatus::*->value`
- [ ] All `'scheduled'`, `'ongoing'`, `'completed'`, `'cancelled'` replaced with `SessionStatus::*` or `->value`
- [ ] All `'pending'`, `'active'`, `'expired'` replaced with `SubscriptionStatus::*->value`
- [ ] All necessary imports added
- [ ] No backward compatibility code left
- [ ] All tests passing
- [ ] No PHP errors or warnings

## Benefits

1. **Type Safety**: IDE autocomplete and type checking
2. **Refactoring**: Easy to rename enum values
3. **Documentation**: Clear enum usage throughout codebase
4. **No Magic Strings**: Eliminates typos in status values
5. **Production Ready**: Professional enum usage pattern

## Notes

- This is a PRODUCTION refactoring - NO backward compatibility needed
- Use enum constants everywhere, even in queries
- The `->value` suffix is only needed when the field is NOT casted to enum
- Most session models have `status` casted to enum, so use constants directly
- Most attendance fields are strings, so use `->value` suffix

## Total Files to Refactor: 20+

End of Report.
