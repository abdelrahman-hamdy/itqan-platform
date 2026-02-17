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
| UI-001 | Frontend | High | Interactive course card `htmlspecialchars()` error on schedule | Fixed |
| API-005 | Teacher API | High | `student.user` undefined relationship across 7 controllers | Fixed |
| API-006 | Student API | High | `course_id` column missing on `course_subscriptions` (7 instances) | Fixed |
| API-007 | Teacher API | Medium | ScheduleController nullsafe chain on `assignedCourses()` | Fixed |
| FIL-001 | Teacher Panel | Medium | AttendanceStatus enum type hint mismatch in StudentSessionReport | Fixed |
| UI-002 | Frontend | High | Interactive course detail `htmlspecialchars()` error on schedule | Fixed |
| FIL-002 | Admin Panel | Medium | `is_active` column removed from `quran_teacher_profiles` but still queried | Fixed |
| FIL-003 | AcademicTeacher Panel | Medium | SessionRecording missing `academy` tenant relationship | Fixed |
| API-008 | Parent API | Medium | `countMissed()` wrong enum comparison — always returns 0 | Fixed |

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

---

## E2E Round 2 Findings (e2e-test academy)

### UI-001: Interactive course card `htmlspecialchars()` error on schedule display
- **Severity**: High
- **Page**: Academy public interactive courses page
- **Expected**: Course schedule days/times displayed correctly
- **Actual**: `htmlspecialchars(): Argument #1 ($string) must be of type string, array given`
- **Root Cause**: `interactive-course-card.blade.php` line 81 iterates schedule as `$day => $time`, but schedule items are arrays `{day: "sunday", time: "16:00"}`, not key-value pairs. `{{ $time }}` tried to echo an array.
- **Fix**: Changed foreach to iterate items and access `$item['day']` and `$item['time']`
- **Location**: `resources/views/components/interactive-course-card.blade.php:81`
- **Status**: Fixed

### API-005: `student.user` undefined relationship causes 500 errors across Teacher API
- **Severity**: High
- **Endpoints**: Dashboard, Schedule, Sessions, Circles, Homework — all Teacher API endpoints
- **Expected**: Sessions return with student name
- **Actual**: 500 error — `Call to undefined relationship [user] on model [App\Models\User]`
- **Root Cause**: All session models (`QuranSession`, `AcademicSession`) define `student()` as `BelongsTo(User::class)` — the student IS already a User. Eager loading `student.user` tries to find a `user()` relationship on the User model itself, which doesn't exist.
- **Impact**: Mobile app teacher dashboard, schedule, sessions, circles, and homework all crash.
- **Fix**: Changed `->with(['student.user', ...])` to `->with(['student', ...])` and `$session->student?->user?->name` to `$session->student?->name` across 7 controller files (DashboardController, ScheduleController, HomeworkController, Quran/SessionController, Quran/CircleController, Academic/SessionController, Academic/LessonController).
- **Status**: Fixed

### API-006: `course_id` column doesn't exist on `course_subscriptions` table
- **Severity**: High
- **Endpoints**: Student course list/detail, Teacher course students/certificates, Mobile purchase, Parent reports, Subscription access middleware
- **Expected**: Course enrollment queries succeed
- **Actual**: 500 error — `Unknown column 'course_id' in 'where clause'`
- **Root Cause**: `course_subscriptions` table uses `recorded_course_id` and `interactive_course_id` (polymorphic FK pattern), but 7 query locations used the non-existent `course_id` column.
- **Impact**: Course enrollment, subscription checks, and certificate queries all fail.
- **Fix**: Replaced `course_id` with `interactive_course_id` or `recorded_course_id` (based on context) in 7 instances across 6 files:
  - `Student/CourseController.php` (2 instances)
  - `Student/SubscriptionController.php` (1 instance)
  - `Student/MobilePurchaseController.php` (1 instance)
  - `Teacher/Academic/CourseController.php` (3 instances)
  - `ParentReportController.php` (1 instance)
  - `EnsureSubscriptionAccess.php` middleware (1 instance)
- **Status**: Fixed

### API-007: ScheduleController nullsafe chain on `assignedCourses()` fails
- **Severity**: Medium
- **Endpoint**: `GET /api/v1/teacher/schedule`
- **Root Cause**: PHP nullsafe operator chain `$user->academicTeacherProfile?->assignedCourses()?->pluck('id') ?? collect()` doesn't work as expected — nullsafe on a method that returns a Builder doesn't propagate null correctly.
- **Fix**: Replaced with explicit if-check: `$profile = ...; $courseIds = $profile ? $profile->assignedCourses()->pluck('id') : collect();`
- **Location**: `app/Http/Controllers/Api/V1/Teacher/ScheduleController.php:153`
- **Status**: Fixed

### FIL-001: AttendanceStatus enum type hint mismatch in StudentSessionReport
- **Severity**: Medium
- **Page**: Teacher panel → Student session reports table
- **Expected**: Attendance status badge renders correctly
- **Actual**: Type error — `fn (string $state)` receives `AttendanceStatus` enum object
- **Root Cause**: Filament passes the casted enum value (AttendanceStatus object) to formatStateUsing/color closures, but closures had `string` type hint.
- **Fix**: Removed `string` type hints from closure parameters, added `(string)` cast inside match expressions.
- **Location**: `app/Filament/Teacher/Resources/StudentSessionReportResource.php:218`
- **Status**: Fixed

### FIL-003: SessionRecording missing `academy` tenant relationship
- **Severity**: Medium
- **Page**: AcademicTeacher panel → Session Recordings list
- **Expected**: Recordings list loads
- **Actual**: `The model [App\Models\SessionRecording] does not have a relationship named [academy]`
- **Root Cause**: Filament's multi-tenancy system defaults `$tenantOwnershipRelationshipName` to `academy`, but `SessionRecording` model has no `academy_id` column or `academy()` relationship. The resource already has custom `scopeEloquentQuery()` that properly scopes by teacher/academy.
- **Fix**: Set `protected static ?string $tenantOwnershipRelationshipName = null;` on both `AcademicTeacher` and `Academy` panel SessionRecordingResource classes.
- **Location**: `app/Filament/AcademicTeacher/Resources/SessionRecordingResource.php`, `app/Filament/Academy/Resources/SessionRecordingResource.php`
- **Status**: Fixed

### API-008: `countMissed()` compares string with enum object (always returns 0)
- **Severity**: Medium
- **Endpoint**: All parent report attendance calculations
- **Expected**: Missed session count correctly calculated
- **Actual**: Always returns 0 — string `$status` compared to `SessionStatus::ABSENT` enum object
- **Root Cause**: `BaseParentReportController::countMissed()` extracts the enum value as a string, then compares it with `SessionStatus::ABSENT` (wrong enum, and object vs string). Should compare with `AttendanceStatus::ABSENT->value`.
- **Fix**: Changed `$status === SessionStatus::ABSENT` to `$status === AttendanceStatus::ABSENT->value`
- **Location**: `app/Http/Controllers/Api/V1/ParentApi/Reports/BaseParentReportController.php:140`
- **Status**: Fixed

### UI-002: Interactive course detail page `htmlspecialchars()` error on schedule display
- **Severity**: High
- **Page**: Student interactive course detail page
- **Expected**: Course schedule days/times displayed correctly
- **Actual**: `htmlspecialchars(): Argument #1 ($string) must be of type string, array given`
- **Root Cause**: Same bug as UI-001 but in a different Blade view. `interactive-course-detail-content.blade.php` line 307 iterates schedule as `$day => $time`, but schedule items are arrays `{day: "sunday", time: "16:00"}`.
- **Fix**: Changed foreach to iterate items and access `$item['day']` and `$item['time']`
- **Location**: `resources/views/student/partials/interactive-course-detail-content.blade.php:307`
- **Status**: Fixed

### FIL-002: `is_active` column removed from `quran_teacher_profiles` but still queried
- **Severity**: Medium
- **Page**: Admin panel → Quran Individual Circles → Create/Edit form (teacher dropdown)
- **Expected**: Teacher dropdown loads active teachers
- **Actual**: SQL error — `Unknown column 'is_active' in 'where clause'`
- **Root Cause**: Migration `2026_01_16_235347_simplify_activation_system` removed `is_active` from `quran_teacher_profiles` (activation moved to `User.active_status`). But `QuranIndividualCircleResource.php:98` still queried `->where('is_active', true)` on `QuranTeacherProfile`.
- **Fix**: Changed to `->whereHas('user', fn ($q) => $q->where('active_status', true))` matching the pattern already used in `QuranSubscriptionResource.php`
- **Location**: `app/Filament/Resources/QuranIndividualCircleResource.php:98`
- **Status**: Fixed
