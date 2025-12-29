# Policies Implementation Summary

## Overview
Created 6 missing authorization policies for the Itqan Platform following the existing patterns and Laravel best practices. All policies have been registered in `AppServiceProvider` and formatted with Laravel Pint.

## Created Policies

### 1. InteractiveCoursePolicy
**File**: `/app/Policies/InteractiveCoursePolicy.php`

**Purpose**: Authorization for InteractiveCourse model

**Permissions**:
- `viewAny()` - admin, supervisor, teacher, student, parent
- `view()` - admin/supervisor (same academy), assigned teacher, enrolled student, parent of enrolled
- `create()` - admin, supervisor
- `update()` - admin/supervisor (same academy), assigned teacher
- `delete()` - admin, supervisor (same academy)
- `publish()` - admin, supervisor (same academy)
- `enroll()` - student (if enrollment open and not already enrolled)
- `manageEnrollments()` - admin/supervisor (same academy), assigned teacher

**Helper Methods**:
- `isEnrolled()` - Check if student is enrolled
- `isParentOfEnrolledStudent()` - Check if parent's child is enrolled
- `sameAcademy()` - Tenant context validation

---

### 2. InteractiveCourseSessionPolicy
**File**: `/app/Policies/InteractiveCourseSessionPolicy.php`

**Purpose**: Authorization for InteractiveCourseSession model

**Permissions**:
- `viewAny()` - admin, supervisor, teacher, student, parent
- `view()` - admin/supervisor (same academy), teacher (own course), enrolled student, parent of enrolled
- `create()` - admin, supervisor, teacher
- `update()` - admin/supervisor (same academy), assigned teacher
- `delete()` - admin, supervisor (same academy)
- `start()` - admin/supervisor (same academy), assigned teacher
- `complete()` - admin/supervisor (same academy), assigned teacher
- `cancel()` - admin/supervisor (same academy), assigned teacher
- `attendSession()` - enrolled student, assigned teacher

**Helper Methods**:
- `isEnrolled()` - Check if student is enrolled in course
- `isParentOfEnrolledStudent()` - Check parent relationship
- `sameAcademy()` - Tenant context validation

---

### 3. MeetingAttendancePolicy
**File**: `/app/Policies/MeetingAttendancePolicy.php`

**Purpose**: Authorization for MeetingAttendance model

**Permissions**:
- `viewAny()` - admin, supervisor, quran_teacher, academic_teacher
- `view()` - admin/supervisor (same academy), session teacher, student (own record), parent of student
- `create()` - **System only** (no manual creation)
- `update()` - admin/supervisor (same academy), session teacher
- `delete()` - **admin only**

**Helper Methods**:
- `isSessionTeacher()` - Check if user is teacher of the session (polymorphic)
- `isParentOfStudent()` - Check parent relationship
- `sameAcademy()` - Tenant context validation (handles polymorphic sessions)

**Special Notes**:
- Attendance records are created automatically by the system
- Supports polymorphic sessions (Quran, Academic)
- Manual creation is explicitly disabled

---

### 4. AcademyPolicy
**File**: `/app/Policies/AcademyPolicy.php`

**Purpose**: Authorization for Academy model

**Permissions**:
- `viewAny()` - super_admin, admin
- `view()` - super_admin (all), admin (own academy), users belonging to academy
- `create()` - **super_admin only**
- `update()` - super_admin (all), admin (own academy)
- `delete()` - **super_admin only**
- `restore()` - **super_admin only**
- `forceDelete()` - **super_admin only**
- `manageSettings()` - super_admin (all), admin (own academy)
- `manageUsers()` - super_admin (all), admin (own academy)

**Special Notes**:
- Only super admins can create/delete academies
- Academy admins can only manage their own academy
- No tenant context needed (Academy IS the tenant)

---

### 5. RecordingPolicy
**File**: `/app/Policies/RecordingPolicy.php`

**Purpose**: Authorization for SessionRecording model

**Permissions**:
- `viewAny()` - admin, supervisor, quran_teacher, academic_teacher
- `view()` - admin/supervisor (same academy), session teacher, enrolled student, parent of enrolled
- `create()` - **System only** (automated recording creation)
- `delete()` - admin, supervisor (same academy)
- `download()` - admin/supervisor, session teacher, enrolled student, parent (if recording available)

**Helper Methods**:
- `isSessionTeacher()` - Polymorphic teacher check (Interactive/Academic/Quran)
- `isEnrolledStudent()` - Polymorphic enrollment check
- `isParentOfEnrolledStudent()` - Polymorphic parent check
- `sameAcademy()` - Tenant context validation (handles polymorphic sessions)

**Special Notes**:
- Recordings are created automatically by the system
- Download only available if recording is completed
- Supports polymorphic sessions (Interactive, Academic, Quran)

---

### 6. TeacherPayoutPolicy
**File**: `/app/Policies/TeacherPayoutPolicy.php`

**Purpose**: Authorization for TeacherPayout model

**Permissions**:
- `viewAny()` - admin, supervisor, quran_teacher, academic_teacher
- `view()` - admin/supervisor (same academy), teacher (own payouts)
- `create()` - **admin only**
- `update()` - **admin only** (before paid status)
- `delete()` - **admin only** (before paid status)
- `process()` - **admin only** (approve/reject pending)
- `markAsPaid()` - **admin only** (approved payouts)

**Helper Methods**:
- `isOwnPayout()` - Check if payout belongs to teacher (polymorphic)
- `sameAcademy()` - Tenant context validation

**Special Notes**:
- Only admins can create and process payouts
- Cannot update/delete payouts after they're paid
- Process permission checks if payout can be approved/rejected
- Supports polymorphic teacher types (Quran/Academic)

---

## Registration

All policies have been registered in `/app/Providers/AppServiceProvider.php`:

```php
// Interactive Course policies
Gate::policy(InteractiveCourse::class, InteractiveCoursePolicy::class);
Gate::policy(InteractiveCourseSession::class, InteractiveCourseSessionPolicy::class);

// Meeting Attendance policy
Gate::policy(MeetingAttendance::class, MeetingAttendancePolicy::class);

// Academy policy
Gate::policy(Academy::class, AcademyPolicy::class);

// Recording policy
Gate::policy(SessionRecording::class, RecordingPolicy::class);

// Teacher Payout policy
Gate::policy(TeacherPayout::class, TeacherPayoutPolicy::class);
```

## Common Patterns

All policies follow these established patterns:

### 1. Role-Based Authorization
- Use `$user->hasRole(['role1', 'role2'])` for role checks
- Support for: super_admin, admin, supervisor, teacher, quran_teacher, academic_teacher, student, parent

### 2. Tenant Context
- `sameAcademy()` helper method in all policies
- Super admin context via `AcademyContextService::getCurrentAcademyId()`
- Null academy ID for super admin = global view access

### 3. Polymorphic Relationships
- Handle multiple session types (Quran, Academic, Interactive)
- Teacher type polymorphism (QuranTeacherProfile, AcademicTeacherProfile)
- Check correct relationship paths based on model type

### 4. Parent Access
- Parents can view children's data
- Use `$user->parentProfile->students()` to get child relationships
- Check via StudentProfile IDs, not User IDs directly

### 5. Null Safety
- Always check for null relationships (`$model->relationship?->property`)
- Return false if required relationships are missing
- Use optional chaining for safety

## Code Quality

- ✅ All files pass PHP syntax validation
- ✅ All files formatted with Laravel Pint
- ✅ Application bootstraps successfully
- ✅ No syntax errors detected
- ✅ Follows existing codebase patterns

## Usage Examples

### Check Interactive Course Access
```php
// In Controller
$this->authorize('view', $course);

// In Blade
@can('enroll', $course)
    <button>Enroll Now</button>
@endcan

// In Code
if (auth()->user()->can('update', $course)) {
    // Update course
}
```

### Check Recording Download
```php
// Ensure recording is available and user has permission
if (auth()->user()->can('download', $recording)) {
    return response()->download($recording->file_path);
}
```

### Check Payout Management
```php
// Only admins can mark as paid
if (auth()->user()->can('markAsPaid', $payout)) {
    $payout->update(['status' => 'paid']);
}
```

## Testing Recommendations

1. **Unit Tests** - Test each permission method with different roles
2. **Feature Tests** - Test full authorization flows
3. **Edge Cases** - Test null relationships, cross-academy access
4. **Parent Access** - Test parent viewing children's data
5. **Polymorphic** - Test all session types for Recording policy

## Notes

- **System-only creation**: MeetingAttendance and SessionRecording cannot be manually created
- **Admin restrictions**: Academy, TeacherPayout have admin-only operations
- **Status-based**: TeacherPayout permissions depend on payout status
- **Tenant safety**: All policies enforce academy isolation except for super_admin
- **Enrollment checks**: Interactive course policies verify enrollment status

## Files Modified

1. `/app/Policies/InteractiveCoursePolicy.php` (NEW)
2. `/app/Policies/InteractiveCourseSessionPolicy.php` (NEW)
3. `/app/Policies/MeetingAttendancePolicy.php` (NEW)
4. `/app/Policies/AcademyPolicy.php` (NEW)
5. `/app/Policies/RecordingPolicy.php` (NEW)
6. `/app/Policies/TeacherPayoutPolicy.php` (NEW)
7. `/app/Providers/AppServiceProvider.php` (MODIFIED - added policy registrations)

Total: 6 new policies + 1 updated service provider
