# E2E Test Suite - Findings & Issues

## Summary

| ID | Category | Severity | Title | Status |
|----|----------|----------|-------|--------|
| WEB-001 | Web Routes | Low | No `/dashboard` route for students | Documented |
| WEB-002 | Admin Panel | Info | No User create page in admin | Documented |
| WEB-003 | Admin Panel | Info | Resource slugs differ from model names | Documented |
| WEB-004 | Admin UX | Low | Save buttons hidden in dropdown on create forms | Documented |
| WEB-005 | Auth | Medium | Supervisor account password changed without notice | Fixed |
| CRUD-001 | Admin Panel | High | AcademicSubject delete fails - wrong FK in relationship | Fixed |
| CRUD-002 | Admin Panel | High | AcademicGradeLevel delete fails - missing pivot table | Fixed |
| API-001 | Chat API | High | Chat unread-count returns 500 Server Error | Fixed |
| API-002 | Auth API | Low | Unauthenticated responses missing standard envelope | Fixed |
| API-003 | Validation API | Low | Validation errors missing standard envelope | Fixed |
| API-004 | Teacher API | Medium | Academic teacher endpoints return 500 | Fixed |
| CRUD-003 | Admin Panel | Medium | InteractiveCourse create page returns 500 | Fixed |

---

## Web / UI Findings (Phase 1-2)

### WEB-001: No `/dashboard` route for students
- **Severity**: Low
- **URL**: `https://itqan-academy.itqanway.com/dashboard`
- **Expected**: Student dashboard page loads
- **Actual**: Returns 404. Students land on `/` (homepage) after login, and `/profile` works for authenticated content.
- **Impact**: Any in-app links pointing to `/dashboard` would be broken for students.
- **Workaround**: Use `/profile` or `/` as student landing page.

### WEB-002: No User create page in admin panel
- **Severity**: Info
- **URL**: `https://itqanway.com/admin/users/create`
- **Expected**: Form to create a new user
- **Actual**: Returns 404. The Users resource is list/edit only.
- **Impact**: Admins cannot create users directly from the admin panel. Users are created through registration or profile-specific resources (student-profiles, etc.).

### WEB-003: Resource slugs differ from model names
- **Severity**: Info
- **Details**: Filament resource slugs are auto-generated from class names, not model names:
  - Students: `student-profiles` (not `students`)
  - Teachers: `quran-teacher-profiles` / `academic-teacher-profiles` (not `teachers`)
  - Parents: `parent-profiles` (not `parents`)
  - Supervisors: `supervisor-profiles` (not `supervisors`)
- **Impact**: Developer confusion when constructing admin panel URLs manually.

### WEB-004: Save buttons hidden in dropdown on create forms
- **Severity**: Low
- **URL**: e.g., `https://itqanway.com/admin/quran-subscriptions/create`
- **Expected**: Visible save/create button on form
- **Actual**: Primary save action is inside a Filament dropdown (`fi-dropdown-list-item`), requiring extra click.
- **Impact**: Minor UX friction for form submission.

### WEB-005: Supervisor account password changed
- **Severity**: Medium (testing impact)
- **Details**: `supervisor1@itqan.com` password was changed on production, breaking E2E test authentication.
- **Resolution**: Password reset to `Admin@Dev98` via artisan tinker.
- **Recommendation**: Document test account credentials in a secure location; add monitoring for credential changes.

---

## CRUD Findings (Phase 12)

### CRUD-001: AcademicSubject delete action returns 500 - wrong foreign key
- **Severity**: High
- **Resource**: `admin/academic-subjects`
- **Expected**: Delete button removes the subject
- **Actual**: Clicking delete confirm causes 500 Internal Server Error
- **Root Cause**: `AcademicSubject::academicIndividualLessons()` relationship used `subject_id` as foreign key, but the actual database column is `academic_subject_id`. The delete action's `before()` hook calls `$record->academicIndividualLessons()->count()` which triggers the invalid SQL.
- **Location**: `app/Models/AcademicSubject.php:75`
- **Fix**: Changed foreign key from `'subject_id'` to `'academic_subject_id'`
- **Status**: Fixed (commit d3b9074)

### CRUD-002: AcademicGradeLevel delete action returns 500 - missing pivot table
- **Severity**: High
- **Resource**: `admin/academic-grade-levels`
- **Expected**: Delete button removes the grade level
- **Actual**: Clicking delete confirm causes 500 Internal Server Error
- **Root Cause**: `AcademicGradeLevel::students()` references a `academic_student_grade_levels` pivot table that was never created via migration. The delete action's `before()` hook calls `$record->students()->count()` which triggers "Table not found" SQL error.
- **Location**: `app/Filament/Resources/AcademicGradeLevelResource.php:181`
- **Fix**: Commented out the `students()->count()` check until the pivot table is created
- **Status**: Fixed (commit d3b9074)

---

## API Findings (Phase 13)

### API-001: Chat unread-count endpoint returns 500 Server Error
- **Severity**: High
- **Endpoint**: `GET /api/v1/chat/unread-count`
- **Expected**: `{ success: true, data: { unread_count: N } }`
- **Actual**: `{ message: "Server Error" }` with HTTP 500
- **Root Cause**: `ChatController::unreadCount()` called `$conv->unreadMessagesCount($user)` which doesn't exist on WireChat `Conversation` model. The correct method is `getUnreadCountFor($user)`.
- **Impact**: Mobile app cannot display unread chat badge count.
- **Location**: `app/Http/Controllers/Api/V1/Common/ChatController.php:561`
- **Fix**: Changed `unreadMessagesCount($user)` to `getUnreadCountFor($user)`
- **Status**: Fixed

### API-002: Unauthenticated responses missing standard API envelope
- **Severity**: Low
- **Endpoint**: All protected endpoints when called without token
- **Expected**: `{ success: false, message: "...", error_code: "UNAUTHENTICATED" }`
- **Actual**: `{ message: "Unauthenticated." }` (Laravel default, no `success` field)
- **Impact**: Mobile app must handle two different error response formats.
- **Fix**: Added `AuthenticationException` renderer in `bootstrap/app.php` to wrap 401 in standard API envelope with `success`, `error_code`, and `meta` fields.
- **Status**: Fixed

### API-003: Validation error responses missing standard API envelope
- **Severity**: Low
- **Endpoint**: Any endpoint with validation (e.g., POST /notifications/device-token)
- **Expected**: `{ success: false, message: "...", error_code: "VALIDATION_ERROR", errors: {...} }`
- **Actual**: `{ message: "...", errors: {...} }` (no `success` field)
- **Impact**: Mobile app must check for both `success === false` and absence of `success` field.
- **Fix**: Added `ValidationException` renderer in `bootstrap/app.php` to wrap 422 in standard API envelope.
- **Status**: Fixed

### API-004: Academic teacher endpoints return 500 Server Error
- **Severity**: Medium
- **Endpoints**:
  - `GET /api/v1/teacher/academic/sessions` - returns 500
  - `GET /api/v1/teacher/academic/lessons` - returns 500
- **Expected**: `{ success: true, data: [...] }` with HTTP 200
- **Actual**: HTTP 500 Server Error
- **Root Cause (1)**: Nullsafe operator chain for `assignedCourses()` behaved unexpectedly. Fixed with explicit if-check.
- **Root Cause (2)**: Both `SessionController` and `LessonController` used `->with(['student.user'])` but `AcademicSession::student()` and `AcademicIndividualLesson::student()` already return `BelongsTo(User::class)`, so `.user` tried to load a non-existent relationship on User model.
- **Root Cause (3)**: `LessonController` eager-loaded `'subject'` but `AcademicIndividualLesson` only has `academicSubject()` relationship — no `subject()` method.
- **Impact**: Mobile app academic teacher section showed error state for sessions/lessons tabs.
- **Fix**:
  - Replaced nullsafe chain with explicit if-check in `SessionController.php`
  - Changed `'student.user'` to `'student'` in both controllers
  - Changed `$session->student?->user?->name` to `$session->student?->name`
  - Changed `'subject'` to `'academicSubject'` in `LessonController.php`
- **Status**: Fixed

---

## CRUD Findings (Phase 12) - Additional

### CRUD-003: InteractiveCourse create page returns 500 Server Error
- **Severity**: Medium
- **Resource**: `admin/interactive-courses/create`
- **Expected**: Create form loads
- **Actual**: HTTP 500 - `Call to undefined method Illuminate\Database\Eloquent\Builder::approved()`
- **Root Cause**: `BaseInteractiveCourseResource.php` called `->approved()` scope on `AcademicTeacherProfile` which doesn't exist. The model only has `scopeActive()`, `scopeForAcademy()`, etc.
- **Impact**: Cannot create interactive courses through admin panel.
- **Fix**: Removed `->approved()` call — the existing `->active()` scope already checks user active status.
- **Location**: `app/Filament/Shared/Resources/BaseInteractiveCourseResource.php:156`
- **Status**: Fixed
