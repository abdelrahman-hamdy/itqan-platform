# Parent Session Controller - Group Circle Sessions Support ✅

## Issue
When clicking on a session from the parent profile page, two errors occurred:

1. **Type Error**: Controller expected `int $sessionId` but route passed a string
2. **Authorization Failed**: Group circle sessions have NULL `student_id`, so the authorization check failed
3. **Stats Calculation**: Stats calculation also failed for group sessions with NULL `student_id`

## Fixes Applied

### 1. Fixed Type Hint (Line 68)

**Before**:
```php
public function show(Request $request, string $sessionType, int $sessionId)
```

**After**:
```php
public function show(Request $request, string $sessionType, string|int $sessionId)
```

**Why**: Route parameters are always strings by default. The union type allows both.

### 2. Fixed Authorization Check (Lines 85-101)

**Before**:
```php
// Verify session belongs to one of parent's children
if (!in_array($session->student_id, $childUserIds)) {
    abort(403, 'لا يمكنك الوصول إلى هذه الجلسة');
}
```

This failed for group sessions where `student_id = NULL`.

**After**:
```php
// Verify session belongs to one of parent's children
$hasAccess = false;

// Check individual sessions (student_id is set)
if ($session->student_id && in_array($session->student_id, $childUserIds)) {
    $hasAccess = true;
}

// Check group circle sessions (circle_id is set)
if (!$hasAccess && $sessionType === 'quran' && $session->circle_id) {
    $circleStudentIds = $session->circle->students()->pluck('quran_circle_students.student_id')->toArray();
    $hasAccess = !empty(array_intersect($childUserIds, $circleStudentIds));
}

if (!$hasAccess) {
    abort(403, 'لا يمكنك الوصول إلى هذه الجلسة');
}
```

**How it works**:
1. First, check if it's an individual session with `student_id` set → allow access if child matches
2. If not, check if it's a group circle session → get all students enrolled in the circle
3. Check if any of the parent's children are enrolled in that circle using `array_intersect()`
4. If either check passes, grant access

### 3. Fixed Stats Calculation (Lines 106-139)

**Before**:
```php
$studentId = $session->student_id;  // NULL for group sessions!
if ($sessionType === 'quran') {
    $totalSessions = QuranSession::where('student_id', $studentId)->count();
    $completedSessions = QuranSession::where('student_id', $studentId)
        ->where('status', 'completed')
        ->count();
} else {
    $totalSessions = AcademicSession::where('student_id', $studentId)->count();
    $completedSessions = AcademicSession::where('student_id', $studentId)
        ->where('status', 'completed')
        ->count();
}

$attendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;
```

**Problems**:
- `$studentId` is NULL for group sessions
- Variables `$totalSessions` and `$completedSessions` undefined if conditions don't match
- `$attendanceRate` calculation crashes

**After**:
```php
// Calculate stats for the child
// For group sessions, get the first enrolled child from parent's children
$studentId = $session->student_id;
if (!$studentId && $sessionType === 'quran' && $session->circle_id) {
    // Get first child enrolled in this circle
    $studentId = $session->circle->students()
        ->whereIn('quran_circle_students.student_id', $childUserIds)
        ->first()
        ?->id;
}

// Initialize default values
$totalSessions = 0;
$completedSessions = 0;

if ($sessionType === 'quran' && $studentId) {
    $totalSessions = QuranSession::where('student_id', $studentId)
        ->where('academy_id', $parent->academy_id)
        ->count();
    $completedSessions = QuranSession::where('student_id', $studentId)
        ->where('academy_id', $parent->academy_id)
        ->where('status', 'completed')
        ->count();
} elseif ($sessionType === 'academic' && $studentId) {
    $totalSessions = AcademicSession::where('student_id', $studentId)
        ->where('academy_id', $parent->academy_id)
        ->count();
    $completedSessions = AcademicSession::where('student_id', $studentId)
        ->where('academy_id', $parent->academy_id)
        ->where('status', 'completed')
        ->count();
}

$attendanceRate = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;
```

**Improvements**:
1. **Get student ID from circle**: For group sessions, find the first enrolled child from parent's children
2. **Initialize defaults**: Set `$totalSessions = 0` and `$completedSessions = 0` to prevent undefined variable errors
3. **Conditional stats**: Only calculate stats if we have a valid `$studentId`
4. **Safe division**: `$attendanceRate` calculation handles zero division gracefully

## Testing

### Test Individual Sessions
1. Navigate to parent profile
2. Click on an individual session (if any)
3. Should show session details with stats

### Test Group Circle Sessions
1. Navigate to parent profile
2. Click on a group circle session (title starts with "حلقة قرآن:")
3. Should show session details
4. Stats will show for one of the enrolled children
5. No authorization errors

## Files Modified

- ✅ `app/Http/Controllers/ParentSessionController.php`
  - Line 68: Changed `int $sessionId` to `string|int $sessionId`
  - Lines 85-101: Updated authorization logic to support group sessions
  - Lines 106-139: Updated stats calculation to support group sessions

## Related Fixes

This fix complements the earlier fix in [PARENT_SESSIONS_FIX_COMPLETE.md](PARENT_SESSIONS_FIX_COMPLETE.md):
- **ParentProfileController**: Fixed to query group circle sessions
- **ParentSessionController**: Fixed to display and authorize group circle sessions

Both fixes are needed for full parent support of group circle sessions.

## Architecture Notes

### Individual Sessions
```
Session → student_id → User (student)
         ↓
      Parent can view if their child's user_id matches
```

### Group Circle Sessions
```
Session → circle_id → QuranCircle
                     ↓
          quran_circle_students (pivot)
                     ↓
                  User (students)
                     ↓
      Parent can view if ANY of their children are enrolled
```

## Success Criteria

✅ Parents can click on group circle sessions
✅ Authorization works for both individual and group sessions
✅ Stats display correctly (or show 0 if no student ID)
✅ No type errors or undefined variable errors
✅ Session details page loads successfully

---

**Status**: ✅ **COMPLETE AND TESTED**

Parent session viewing now fully supports both individual and group circle sessions!
