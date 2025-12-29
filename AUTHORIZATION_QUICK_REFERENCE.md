# Authorization Quick Reference Guide

## Controllers Standardized (High Priority) ✅

| Controller | Abort Calls | Policy Used | Status |
|-----------|-------------|-------------|--------|
| AcademicSessionController | 3 | SessionPolicy | ✅ Complete |
| QuranSessionController | 3 | SessionPolicy | ✅ Complete |
| ParentDashboardController | 4 | ParentPolicy | ✅ Complete |
| QuizController | 6 | QuizAssignmentPolicy | ✅ Complete |
| LessonController | 2 | LessonPolicy (NEW) | ✅ Complete |
| RecordedCourseController | 1 | CourseSubscriptionPolicy | ✅ Complete |
| ParentSubscriptionController | 2 | SubscriptionPolicy | ✅ Complete |
| CertificateController | 0 | CertificatePolicy | ✅ Already Compliant |
| PaymentController | 0 | PaymentPolicy | ✅ Already Compliant |

**Total**: 9 controllers, 21 abort calls replaced

## New Policies Created

### 1. LessonPolicy
**File**: `/app/Policies/LessonPolicy.php`

**Methods**:
- `viewAny(User $user)`: Anyone can browse lessons
- `view(User $user, Lesson $lesson)`: Free preview OR enrolled student
- `create(User $user)`: Admins and teachers only
- `update(User $user, Lesson $lesson)`: Admins and course creators
- `delete(User $user, Lesson $lesson)`: Admins only
- `downloadMaterials(User $user, Lesson $lesson)`: Must be downloadable + have view access

**Key Features**:
- Free preview lessons accessible to all
- Enrollment-based access control
- Parent access to children's lessons
- Multi-tenant academy scoping

## Policy Enhancements

### 1. ParentPolicy
**Added Method**: `viewDashboard(User $user)`

**Purpose**: Authorize parent access to dashboard
```php
public function viewDashboard(User $user): bool
{
    return $user->isParent() && $user->parentProfile !== null;
}
```

### 2. QuizAssignmentPolicy
**Added Methods**:
- `start(User $user, QuizAssignment $assignment)`: Can start quiz
- `take(User $user, $attempt)`: Can take quiz attempt
- `submit(User $user, $attempt)`: Can submit quiz attempt
- `viewResult(User $user, QuizAssignment $assignment)`: Can view results

## Common Authorization Patterns

### Pattern 1: Simple Role Check
```php
// Controller
public function index()
{
    $this->authorize('viewAny', Model::class);
    // ... rest of method
}

// Policy
public function viewAny(User $user): bool
{
    return $user->hasRole(['admin', 'teacher', 'student']);
}
```

### Pattern 2: Ownership Check
```php
// Controller
public function show($id)
{
    $model = Model::findOrFail($id);
    $this->authorize('view', $model);
    // ... rest of method
}

// Policy
public function view(User $user, Model $model): bool
{
    return $model->user_id === $user->id;
}
```

### Pattern 3: Parent-Child Relationship
```php
// Controller
public function viewChild($childId)
{
    $child = StudentProfile::findOrFail($childId);
    $this->authorize('viewChild', [$parent, $child]);
    // ... rest of method
}

// Policy
public function viewChild(User $user, StudentProfile $student): bool
{
    $parent = $user->parentProfile;
    return $parent->students()->where('id', $student->id)->exists();
}
```

### Pattern 4: Multi-Tenant Academy Scoping
```php
private function sameAcademy(User $user, $model): bool
{
    if ($user->hasRole('super_admin')) {
        $userAcademyId = AcademyContextService::getCurrentAcademyId();
        if (!$userAcademyId) {
            return true; // Global view
        }
        return $model->academy_id === $userAcademyId;
    }
    return $model->academy_id === $user->academy_id;
}
```

## Before vs After Examples

### Example 1: AcademicSessionController
```php
// ❌ Before (inline abort)
public function index()
{
    $user = Auth::user();
    if (! $user->isAcademicTeacher()) {
        abort(403, 'غير مسموح لك بالوصول لهذه الصفحة');
    }
    // ... rest
}

// ✅ After (policy-based)
public function index()
{
    $this->authorize('viewAny', AcademicSession::class);
    $user = Auth::user();
    // ... rest
}
```

### Example 2: QuizController
```php
// ❌ Before (manual checks)
public function take($attemptId)
{
    $attempt = QuizAttempt::findOrFail($attemptId);
    $student = Auth::user()->studentProfile;

    if (!$student || $attempt->student_id !== $student->id) {
        abort(403, 'غير مصرح لك بالوصول إلى هذا الاختبار');
    }
    // ... rest
}

// ✅ After (policy-based)
public function take($attemptId)
{
    $attempt = QuizAttempt::findOrFail($attemptId);
    $this->authorize('take', $attempt);
    $student = Auth::user()->studentProfile;
    // ... rest
}
```

### Example 3: ParentDashboardController
```php
// ❌ Before (inline checks)
public function selectChild($childId)
{
    $user = Auth::user();
    $parent = $user->parentProfile;

    if (!$parent) {
        abort(403, 'لا يمكن الوصول إلى بيانات ولي الأمر');
    }

    $child = $parent->students()->find($childId);
    if (!$child) {
        return redirect()->back()->with('error', 'لا يمكنك الوصول');
    }
    // ... rest
}

// ✅ After (policy-based)
public function selectChild($childId)
{
    $user = Auth::user();
    $parent = $user->parentProfile;
    $child = StudentProfile::findOrFail($childId);

    $this->authorize('viewChild', [$parent, $child]);
    // ... rest
}
```

## Remaining Work

### Medium Priority Controllers (9 controllers, 7 abort calls)
1. CalendarController - 1 call
2. ParentProfileController - 1 call
3. StudentProfileController - 1 call
4. ParentChildrenController - 1 call
5. ParentSessionController - 1 call
6. ParentReportController - 1 call
7. ParentHomeworkController - 1 call
8. TeacherProfileController - 1 call
9. QuranCircleController - 1 call

### Recommended Next Steps
1. Standardize remaining controllers
2. Add policy unit tests
3. Add controller authorization tests
4. Document all policy methods
5. Extract common policy patterns to traits

## Key Benefits

✅ **Centralized Authorization**: All rules in one place
✅ **Testable**: Policies can be unit tested
✅ **Maintainable**: Easy to update and audit
✅ **Consistent**: Same pattern everywhere
✅ **Laravel Best Practice**: Follows framework conventions

## Quick Tips

1. **Always use policies** instead of inline authorization
2. **Name policy methods clearly**: `view`, `update`, `delete`, `viewChild`, etc.
3. **Handle all roles**: Super Admin, Admin, Teacher, Student, Parent
4. **Consider edge cases**: What if user is deleted? What if academy changes?
5. **Test thoroughly**: Unit tests for policies, feature tests for controllers

---

**Last Updated**: 2025-12-29
**Maintained By**: Development Team
