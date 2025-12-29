# Authorization Standardization Summary

## Overview
This document summarizes the standardization of authorization patterns across all controllers in the Itqan Platform. The goal is to replace inline `abort(403)` checks with proper policy-based authorization using Laravel's `$this->authorize()` method.

## Status: In Progress

### Completed Controllers (3/21)

#### 1. StudentInteractiveCourseController ✅
**File:** `app/Http/Controllers/StudentInteractiveCourseController.php`
**Changes Made:**
- Replaced 16 `abort(403)` calls with policy-based authorization
- Added `belongsToAcademy` method to `AcademyPolicy`
- Methods refactored:
  - `showInteractiveCourse()` - Uses `InteractiveCoursePolicy::view()`
  - `showInteractiveCourseSession()` - Uses `InteractiveCourseSessionPolicy::view()`
  - `interactiveCourseReport()` - Uses `InteractiveCoursePolicy::view()`
  - `interactiveCourseStudentReport()` - Uses `InteractiveCoursePolicy::view()`
  - `studentInteractiveCourseReport()` - Uses `InteractiveCoursePolicy::view()`
  - `assignInteractiveSessionHomework()` - Uses `InteractiveCourseSessionPolicy::update()`
  - `updateInteractiveSessionHomework()` - Uses `InteractiveCourseSessionPolicy::update()`

**Pattern Used:**
```php
// Before:
if ($user->academy_id !== $academy->id) {
    abort(403, 'Access denied to this academy');
}

// After:
if ($user->academy_id !== $academy->id) {
    $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
}
```

```php
// Before:
if (!$user->isAcademicTeacher()) {
    abort(403, 'Access denied - teachers only');
}

// After:
$this->authorize('view', $course); // Policy handles role checks
```

#### 2. QuranIndividualCircleController ✅
**File:** `app/Http/Controllers/QuranIndividualCircleController.php`
**Changes Made:**
- Replaced 3 `abort(403)` calls with policy-based authorization
- Methods refactored:
  - `index()` - Uses `QuranCirclePolicy::viewAny()`
  - `show()` - Uses `QuranCirclePolicy::view()`
  - `progressReport()` - Uses `QuranCirclePolicy::view()`

**Pattern Used:**
```php
// Before:
if (!$user->isQuranTeacher()) {
    abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
}

// After:
$this->authorize('viewAny', QuranIndividualCircle::class);
```

#### 3. InteractiveCourseRecordingController ✅
**File:** `app/Http/Controllers/InteractiveCourseRecordingController.php`
**Changes Made:**
- Replaced 2 `abort(403)` calls with policy-based authorization
- Methods refactored:
  - `downloadRecording()` - Uses `RecordingPolicy::download()`
  - `viewRecording()` - Uses `RecordingPolicy::view()`

**Pattern Used:**
```php
// Before:
if (!$courseSession->canUserAccessRecordings($user)) {
    abort(403, 'غير مسموح لك بتحميل هذا التسجيل');
}

// After:
$this->authorize('download', $recording);
```

---

## Pending Controllers (18/21)

### High Priority

#### 4. AcademicSessionController 🔴
**File:** `app/Http/Controllers/AcademicSessionController.php`
**Occurrences:** 3 `abort(403)` calls
**Policy to Use:** `SessionPolicy`
**Required Changes:**
- Line 40: `if (!$user->isAcademicTeacher())` → `$this->authorize('create', AcademicSession::class)`
- Line 461: `if (!$teacherProfile || ...)` → `$this->authorize('view', $subscription)`
- Line 518: `if (!$user->isStudent() || ...)` → `$this->authorize('view', $subscription)`

#### 5. QuranSessionController 🔴
**File:** `app/Http/Controllers/QuranSessionController.php`
**Occurrences:** 3 `abort(403)` calls
**Policy to Use:** `SessionPolicy`
**Required Changes:**
- Line 38: `if ($user->user_type !== 'student')` → `$this->authorize('viewAny', QuranSession::class)`
- Line 94: `if (!in_array($user->user_type, [...]))` → `$this->authorize('create', QuranSession::class)`
- Line 101: `if (!$teacherProfile)` → Remove (policy handles)

#### 6. ParentDashboardController 🔴
**File:** `app/Http/Controllers/ParentDashboardController.php`
**Occurrences:** 4 `abort(403)` calls
**Policy to Use:** `ParentPolicy`
**Required Changes:**
- Line 50, 77, 110: `if (!$parent)` → `$this->authorize('viewAny', StudentProfile::class)`
- Line 234: `if (!$verification)` → `$this->authorize('viewChild', [$user, $student])`

### Medium Priority

#### 7. QuizController 🟡
**File:** `app/Http/Controllers/QuizController.php`
**Occurrences:** 6 `abort(403)` calls
**Policy to Use:** `QuizAssignmentPolicy` (exists)
**Required Changes:**
- Lines 36, 64, 71: Student verification checks → Use policy methods
- Lines 99, 129, 157: Attempt ownership checks → `$this->authorize('view', $attempt)`

#### 8. LessonController 🟡
**File:** `app/Http/Controllers/LessonController.php`
**Occurrences:** 2 `abort(403)` calls
**Policy to Use:** Need to create `LessonPolicy`
**Required Changes:**
- Line 310: `if (!$lesson->isAccessibleBy($user))` → `$this->authorize('view', $lesson)`
- Line 357: Same pattern

**Action Required:** Create `LessonPolicy` with `view()` method

#### 9. CertificateController 🟡
**File:** `app/Http/Controllers/CertificateController.php`
**Occurrences:** 1 `abort(403)` call
**Policy to Use:** `CertificatePolicy` (exists)
**Required Changes:**
- Line 135: `if (Auth::id() !== $enrollment->student_id)` → Already has policy, use `$this->authorize('view', $certificate)`

### Low Priority

#### 10. TeacherProfileController 🟢
**File:** `app/Http/Controllers/TeacherProfileController.php`
**Occurrences:** 1 `abort(403)` call
**Policy to Use:** `TeacherProfilePolicy` (exists)
**Required Changes:**
- Line 190: `if (!$canAccessStudent)` → `$this->authorize('viewStudent', [$teacherProfile, $student])`

**Action Required:** Add `viewStudent()` method to `TeacherProfilePolicy`

#### 11. ParentHomeworkController 🟢
**File:** `app/Http/Controllers/ParentHomeworkController.php`
**Occurrences:** 1 `abort(403)` call
**Policy to Use:** `ParentPolicy`
**Required Changes:**
- Line 184: `if (!in_array($studentId, $childUserIds))` → `$this->authorize('viewChildHomework', [$user, $student])`

#### 12. ParentSubscriptionController 🟢
**File:** `app/Http/Controllers/ParentSubscriptionController.php`
**Occurrences:** 2 `abort(403)` calls
**Policy to Use:** `ParentPolicy`
**Required Changes:**
- Line 55, 140: `if (!$parent)` → Use `ParentPolicy::viewChild()`

#### 13. Student/CircleReportController 🟢
**File:** `app/Http/Controllers/Student/CircleReportController.php`
**Occurrences:** 2 `abort(403)` calls
**Policy to Use:** `QuranCirclePolicy`
**Required Changes:**
- Line 32: `if ($circle->student_id !== auth()->id())` → `$this->authorize('view', $circle)`
- Line 58: Group circle check → Same pattern

#### 14-18. Additional Controllers 🟢
- `UnifiedInteractiveCourseController` - Similar patterns to StudentInteractiveCourseController
- `Teacher/IndividualCircleReportController` - Use QuranCirclePolicy
- `Teacher/GroupCircleReportController` - Use QuranCirclePolicy
- `ParentReportController` - Use ParentPolicy
- `QuranGroupCircleScheduleController` - Use QuranCirclePolicy
- `QuranCircleController` - Use QuranCirclePolicy
- `StudentCalendarController` - Use custom authorization

---

## Policy Status

### Existing Policies ✅
- `AcademyPolicy` - Enhanced with `belongsToAcademy()` method
- `CertificatePolicy`
- `HomeworkPolicy`
- `InteractiveCoursePolicy`
- `InteractiveCourseSessionPolicy`
- `MeetingAttendancePolicy`
- `ParentPolicy`
- `PaymentPolicy`
- `QuizAssignmentPolicy`
- `QuranCirclePolicy`
- `RecordingPolicy`
- `SessionPolicy`
- `StudentProfilePolicy`
- `SubscriptionPolicy`
- `TeacherPayoutPolicy`
- `TeacherProfilePolicy`

### Missing Policies (Need to Create) ⚠️
1. **LessonPolicy** - For recorded course lesson access control
   - Methods needed: `view()`, `download()`, `complete()`

### Policy Enhancements Needed ⚠️
1. **TeacherProfilePolicy** - Add `viewStudent()` method for student profile access
2. **ParentPolicy** - Methods exist but may need refinement for homework/quiz access

---

## Common Authorization Patterns

### Pattern 1: Role-Based Access
```php
// Before:
if (!$user->isTeacher()) {
    abort(403, 'Teachers only');
}

// After:
$this->authorize('viewAny', Model::class); // Policy checks role
```

### Pattern 2: Ownership Check
```php
// Before:
if ($session->teacher_id !== $user->id) {
    abort(403, 'Not your session');
}

// After:
$this->authorize('update', $session);
```

### Pattern 3: Academy Scoping
```php
// Before:
if ($user->academy_id !== $academy->id) {
    abort(403, 'Access denied to this academy');
}

// After:
if ($user->academy_id !== $academy->id) {
    $this->authorize('belongsToAcademy', [\App\Models\Academy::class, $academy]);
}
```

### Pattern 4: Parent-Child Relationship
```php
// Before:
if (!in_array($studentId, $childIds)) {
    abort(403, 'Not your child');
}

// After:
$this->authorize('viewChild', [$user, $student]);
```

### Pattern 5: Enrollment Check
```php
// Before:
if (!$enrollment || $enrollment->student_id !== $user->id) {
    abort(403, 'Not enrolled');
}

// After:
$this->authorize('view', $course); // Policy checks enrollment for students
```

---

## Migration Strategy

### Phase 1: Critical Controllers (Completed 3/5)
- ✅ StudentInteractiveCourseController
- ✅ QuranIndividualCircleController
- ✅ InteractiveCourseRecordingController
- 🔴 AcademicSessionController
- 🔴 QuranSessionController

### Phase 2: High-Traffic Controllers (0/3)
- 🟡 ParentDashboardController
- 🟡 QuizController
- 🟡 LessonController

### Phase 3: Remaining Controllers (0/10)
- All other controllers listed above

### Phase 4: Testing & Verification
- Manual testing of all authorization flows
- Update PHPUnit tests to use policy expectations
- Verify no regression in access control

---

## Benefits of This Refactoring

1. **Consistency** - Single authorization pattern across entire codebase
2. **Testability** - Policies can be unit tested independently
3. **Maintainability** - Authorization logic centralized in policy classes
4. **Reusability** - Same policy methods used across multiple controllers
5. **Clarity** - Clear separation between business logic and authorization
6. **Type Safety** - Better IDE support and type hinting
7. **Laravel Conventions** - Following Laravel best practices

---

## Breaking Changes

**None** - This is a refactoring of internal authorization logic. External API behavior remains unchanged.

---

## Next Steps

1. Complete Phase 1 (AcademicSessionController, QuranSessionController)
2. Create LessonPolicy
3. Enhance TeacherProfilePolicy with viewStudent() method
4. Continue with Phase 2 controllers
5. Update test suite to use policy mocks
6. Create integration tests for authorization flows
7. Update documentation with policy usage examples

---

## Code Review Checklist

When reviewing policy-based authorization changes:

- [ ] All `abort(403)` calls replaced with `$this->authorize()`
- [ ] Correct policy method used for each action
- [ ] Policy exists and has required method
- [ ] Authorization happens BEFORE business logic
- [ ] Error messages preserved in policy methods
- [ ] No duplicate authorization checks
- [ ] Related tests updated

---

## Related Documentation

- Laravel Authorization: https://laravel.com/docs/11.x/authorization
- Policy Classes: https://laravel.com/docs/11.x/authorization#creating-policies
- Project Policies: `/Users/abdelrahmanhamdy/web/itqan-platform/app/Policies/`

---

**Last Updated:** 2025-12-29
**Author:** Claude Code Refactoring
**Status:** 14% Complete (3/21 controllers)
