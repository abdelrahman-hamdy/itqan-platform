# Enum Refactoring Status Report

## Completed Refactoring

### ✅ BaseSessionAttendance.php
- **Status**: COMPLETE
- **Changes Made**:
  - Added `use App\Enums\SessionStatus;` import
  - Line 96: `'attendance_status' => AttendanceStatus::ABSENT->value`
  - Line 335: `=== AttendanceStatus::ATTENDED->value`
  - Line 344: Ternary operator using `AttendanceStatus::LATE->value` and `AttendanceStatus::ATTENDED->value`
  - Lines 406-431: All return statements now use `AttendanceStatus::*->value`
  - Line 508: `=== SessionStatus::ONGOING`
  - Lines 509, 516: `=== AttendanceStatus::ATTENDED->value`, `=== AttendanceStatus::ABSENT->value`
- **Scopes**: Already using enum constants (lines 150-161)

### ✅ BaseSessionReport.php
- **Status**: COMPLETE
- **Changes Made**:
  - Line 373: `return AttendanceStatus::ABSENT->value`
- **Scopes**: Already using enum constants (lines 161-176)

### ✅ BaseSession.php
- **Status**: COMPLETE
- **Changes Made**:
  - Lines 218-222: Match statement arms now use `SessionStatus::*->value` constants
- **Scopes**: Already using `SessionStatus` constants (lines 281-331)
- **Helper Methods**: Already using `SessionStatus` constants (lines 340-367)

### ✅ AcademicSession.php
- **Status**: COMPLETE
- **Changes Made**:
  - Added `use App\Enums\AttendanceStatus;` import
  - Line 99: `'status' => SessionStatus::SCHEDULED->value`
  - Line 102: `'attendance_status' => AttendanceStatus::ABSENT->value` (fixed from invalid 'scheduled')
  - Lines 374-378: Match statement using `SessionStatus::*->value` constants
  - Line 577: `'attendance_status' => AttendanceStatus::ABSENT->value`
  - Line 627: `'attendance_status' => AttendanceStatus::ATTENDED->value`
  - Line 688: `'attendance_status' => AttendanceStatus::ABSENT->value`

## In Progress

### ⏳ QuranSession.php
- **Status**: IN PROGRESS
- **Lines Needing Changes** (from grep):
  1. Line 259: `->where('status', 'missed')` - **ISSUE**: 'missed' not in SessionStatus enum, should use `SessionStatus::ABSENT`
  2. Line 391: `$session->recordSessionAttendance('attended')` → `AttendanceStatus::ATTENDED->value`
  3. Line 420: `$this->recordSessionAttendance('cancelled')` → Keep as string (custom attendance status)
  4. Line 445: `$this->recordSessionAttendance('absent')` → `AttendanceStatus::ABSENT->value`
  5. Line 471: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
  6. Line 485: `'attendance_status' => 'absent'` → `AttendanceStatus::ABSENT->value`
  7. Lines 672-675: Match statement (getAttendanceStatusTextAttribute) → Use `AttendanceStatus::*->value` constants
  8. Line 1063: `'status' => 'scheduled'` → `SessionStatus::SCHEDULED->value`

- **Lines Already Correct**:
  - Line 824: `'attendance_status' => AttendanceStatus::ATTENDED` ✅
  - Line 883: `'attendance_status' => AttendanceStatus::ABSENT` ✅
  - Imports already include both `AttendanceStatus` and `SessionStatus` ✅

## Pending

### 📋 Subscription Models
1. **QuranSubscription.php**
   - Line 711: `!== 'active'` → `!== SubscriptionStatus::ACTIVE->value`

2. **AcademicSubscription.php**
   - Need to search for status literals

3. **BaseSubscription.php**
   - Need to search for status literals

4. **CourseSubscription.php**
   - Need to search for status literals

### 📋 Circle/Lesson Models
1. **QuranCircle.php**
   - Need to search for status literals

2. **QuranIndividualCircle.php**
   - Line 231: `=== 'pending'` - Need to determine if this is subscription status or circle status

3. **AcademicIndividualLesson.php**
   - Line 183: `=== 'active'` - Lesson status (may need custom enum)
   - Line 188: `=== 'completed'` - Lesson status (may need custom enum)

### 📋 Other Models
1. **MeetingAttendance.php**
   - Need to search for attendance_status literals

2. **SessionRequest.php**
   - Line 194: `=== 'expired'` - RequestStatus (different enum needed)

3. **HomeworkSubmission.php**
   - Line 350: `=== 'late'` - submission_status (different enum needed)

4. **InteractiveCourseSession.php**
   - Need to search for session status literals

5. **InteractiveCourseEnrollment.php**
   - Need to search for enrollment status literals

6. **Payment.php**
   - Lines 224, 229: Payment status (different from subscription status)

7. **AcademicSessionReport.php**
   - Line 248: `=== 'completed'` → `SessionStatus::COMPLETED`

8. **Other recording/payout models**
   - CourseRecording.php, SessionRecording.php, TeacherPayout.php

## Known Issues

### Issue 1: 'missed' Status in QuranSession
- **Location**: Line 259
- **Problem**: `->where('status', 'missed')` - 'missed' is not a valid SessionStatus enum value
- **Solution**: Should use `SessionStatus::ABSENT` instead, or create a new enum value
- **Impact**: This is a legacy scope that may need database migration if 'missed' status exists in DB

### Issue 2: 'cancelled' as Attendance Status
- **Location**: Line 420 in QuranSession
- **Problem**: `'cancelled'` is being used as an attendance status, but `AttendanceStatus` enum doesn't have CANCELLED
- **Current Values**: ATTENDED, LATE, LEAVED, ABSENT
- **Solution**: Either add CANCELLED to AttendanceStatus enum, or handle as ABSENT

### Issue 3: Invalid Default Attendance Status
- **Location**: AcademicSession line 101 (FIXED)
- **Problem**: Was using 'scheduled' as attendance_status, which doesn't exist in AttendanceStatus
- **Solution**: Changed to AttendanceStatus::ABSENT->value

## Enum Usage Patterns Identified

### Pattern A: Casted Enum Fields (Direct Comparison)
```php
// When field is casted to enum in $casts
if ($this->status === SessionStatus::COMPLETED) // ✅ Correct
```

### Pattern B: String Fields (Use ->value)
```php
// When field is NOT casted (most attendance_status fields)
if ($this->attendance_status === AttendanceStatus::ATTENDED->value) // ✅ Correct
```

### Pattern C: Match Statements
```php
// When matching against string value
return match ($status) {
    SessionStatus::COMPLETED->value => 'done',
    SessionStatus::ONGOING->value => 'active',
};
```

### Pattern D: Assignments
```php
// In update/create arrays
$this->update(['status' => SessionStatus::COMPLETED]);
$this->update(['attendance_status' => AttendanceStatus::ATTENDED->value]);
```

## Statistics

- **Total Files to Refactor**: ~20
- **Completed**: 4 (BaseSession, BaseSessionAttendance, BaseSessionReport, AcademicSession)
- **In Progress**: 1 (QuranSession)
- **Remaining**: ~15

## Next Steps

1. Complete QuranSession.php refactoring
2. Refactor all subscription models (QuranSubscription, AcademicSubscription, BaseSubscription, CourseSubscription)
3. Refactor circle/lesson models
4. Refactor remaining session-related models (InteractiveCourseSession, MeetingAttendance)
5. Refactor report models (AcademicSessionReport)
6. Handle edge cases (Payment, HomeworkSubmission, SessionRequest with different status types)
7. Run full test suite to verify no regressions
8. Document any enum additions needed (CANCELLED for AttendanceStatus, etc.)

## Testing Checklist

- [ ] All session status comparisons work correctly
- [ ] All attendance status comparisons work correctly
- [ ] All subscription status comparisons work correctly
- [ ] Scopes return correct results
- [ ] Match statements work correctly
- [ ] No PHP type errors
- [ ] All tests pass
- [ ] No database query errors

End of Status Report - Last Updated: 2025-12-27
