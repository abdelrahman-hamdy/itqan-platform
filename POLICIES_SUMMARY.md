# Policy Classes - Implementation Summary

This document summarizes the 6 newly created policy classes that complete the authorization system for the Itqan Platform.

## Overview

All policies have been created following the existing patterns in the codebase and are registered in `app/Providers/AppServiceProvider.php` (lines 191-205).

## Policy Classes Created

### 1. InteractiveCoursePolicy

**File:** `/app/Policies/InteractiveCoursePolicy.php`

**Purpose:** Controls authorization for InteractiveCourse model operations.

**Key Methods:**
- `viewAny()` - All authenticated users can browse courses
- `view()` - Admins, teachers, enrolled students, and parents can view courses
- `create()` - Only super admins and admins can create courses
- `update()` - Admins and assigned teachers can update courses
- `delete()` - Only admins can delete (cannot delete if has active enrollments)
- `enroll()` - Students can enroll if course is open and they're not already enrolled
- `manageEnrollments()` - Admins and assigned teachers can manage enrollments

**Authorization Logic:**
- Academy ownership validation using `AcademyContextService`
- Enrollment status checking for students and parents
- Published course visibility for browsing
- Teacher assignment validation

---

### 2. InteractiveCourseSessionPolicy

**File:** `/app/Policies/InteractiveCourseSessionPolicy.php`

**Purpose:** Controls authorization for InteractiveCourseSession model operations.

**Key Methods:**
- `viewAny()` - All authenticated users can view sessions
- `view()` - Admins, course teachers, enrolled students, and parents can view
- `create()` - Admins and academic teachers can create sessions
- `update()` - Admins and course teachers can update sessions
- `delete()` - Only admins can delete (cannot delete completed sessions)
- `join()` - Course teachers, enrolled students, and admins can join meetings
- `start()` - Only course teacher can start sessions
- `complete()` - Admins and course teachers can complete sessions
- `cancel()` - Admins and course teachers can cancel (if session allows)

**Authorization Logic:**
- Session-course relationship validation
- Enrollment status checking through course
- Session status validation (canStart, canCancel)
- Academy ownership through course relationship

---

### 3. MeetingAttendancePolicy

**File:** `/app/Policies/MeetingAttendancePolicy.php`

**Purpose:** Controls authorization for MeetingAttendance model operations.

**Key Methods:**
- `viewAny()` - Admins and teachers can view attendance records
- `view()` - Admins, session teachers, students (own), and parents can view
- `update()` - Only admins and session teachers can manually adjust attendance
- `delete()` - Only admins can delete attendance records
- `recalculate()` - Admins and session teachers can recalculate attendance

**Authorization Logic:**
- Session type detection (individual, group, academic, interactive)
- Teacher ownership validation based on session type
- Parent-student relationship validation
- Academy ownership validation (handles InteractiveCourseSession special case)

**Special Considerations:**
- Handles polymorphic session relationships
- InteractiveCourseSession academy accessed through course
- QuranSession and AcademicSession use direct academy_id

---

### 4. AcademyPolicy

**File:** `/app/Policies/AcademyPolicy.php`

**Purpose:** Controls authorization for Academy model operations.

**Key Methods:**
- `viewAny()` - All users can view (filtered by permissions)
- `view()` - Super admins, academy owners, and academy members can view
- `create()` - Only super admins can create academies
- `update()` - Super admins, academy admins, and owners can update
- `delete()` - Only super admins (cannot delete default academy)
- `manageSettings()` - Super admins, academy admins, and owners
- `manageBranding()` - Same as manageSettings
- `manageDesign()` - Same as manageSettings
- `viewFinancials()` - Super admins, academy admins, and owners
- `manageUsers()` - Super admins, academy admins, and owners
- `toggleMaintenanceMode()` - Super admins and academy admins only

**Authorization Logic:**
- Super admin always has access
- Academy admin_id validation
- User academy_id validation
- Protection for default academy (itqan-academy)

---

### 5. RecordingPolicy

**File:** `/app/Policies/RecordingPolicy.php`

**Purpose:** Controls authorization for SessionRecording model operations.

**Key Methods:**
- `viewAny()` - All authenticated users can browse recordings
- `view()` - Admins, session teachers, enrolled students, and parents can view
- `download()` - Same as view permission
- `delete()` - Only admins can delete recordings
- `restore()` - Only super admins can restore
- `forceDelete()` - Only super admins can permanently delete

**Authorization Logic:**
- Polymorphic recordable relationship handling
- Session teacher validation (InteractiveCourse, Quran, Academic)
- Enrollment validation based on session type
- Parent-child relationship validation
- Academy ownership through session relationship

**Special Considerations:**
- Handles three session types (InteractiveCourseSession, QuranSession, AcademicSession)
- InteractiveCourseSession enrollment through course
- QuranSession and AcademicSession direct student_id check

---

### 6. TeacherPayoutPolicy

**File:** `/app/Policies/TeacherPayoutPolicy.php`

**Purpose:** Controls authorization for TeacherPayout model operations.

**Key Methods:**
- `viewAny()` - Admins, supervisors, and teachers can view payouts
- `view()` - Admins can view all, teachers can view their own
- `create()` - Only admins can create payouts
- `update()` - Only admins (can only update pending payouts)
- `delete()` - Only admins (can only delete pending/rejected payouts)
- `approve()` - Only admins (can only approve pending payouts)
- `reject()` - Only admins (can reject pending/approved payouts)
- `markPaid()` - Only admins (can only mark approved payouts as paid)
- `restore()` - Only super admins
- `forceDelete()` - Only super admins

**Authorization Logic:**
- Payout ownership validation (QuranTeacherProfile vs AcademicTeacherProfile)
- Status-based authorization (pending, approved, rejected, paid)
- Academy ownership using `AcademyContextService`
- Teacher profile type validation

**Special Considerations:**
- Polymorphic teacher relationship (QuranTeacher vs AcademicTeacher)
- Status workflow enforcement (pending → approved → paid)
- Rejection allowed at pending and approved stages

---

## Policy Registration

All policies are registered in `app/Providers/AppServiceProvider.php` in the `boot()` method:

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

All policies follow these conventions:

1. **Role-based authorization** using `$user->hasRole()`
2. **Academy scoping** using `AcademyContextService` for super admins
3. **Relationship validation** for ownership and access control
4. **Status-based authorization** where applicable (sessions, payouts)
5. **Comprehensive docblocks** explaining authorization logic

## Usage Examples

### In Controllers

```php
// Check if user can view a course
$this->authorize('view', $course);

// Check if user can enroll in a course
if ($user->can('enroll', $course)) {
    // Enroll logic
}

// Check if user can approve a payout
$this->authorize('approve', $payout);
```

### In Blade Templates

```blade
@can('update', $course)
    <a href="{{ route('courses.edit', $course) }}">Edit Course</a>
@endcan

@can('join', $session)
    <a href="{{ route('sessions.join', $session) }}">Join Session</a>
@endcan

@can('download', $recording)
    <a href="{{ route('recordings.download', $recording) }}">Download</a>
@endcan
```

### In Filament Resources

```php
public static function canView(Model $record): bool
{
    return auth()->user()->can('view', $record);
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('update', $record);
}
```

## Testing

All policies should be tested with:

1. Different user roles (super_admin, admin, teacher, student, parent)
2. Academy ownership scenarios
3. Enrollment status variations
4. Status transitions (for sessions and payouts)
5. Edge cases (deleted academies, missing relationships, etc.)

## Notes

- All policies handle the special case of InteractiveCourseSession having no direct academy_id (accessed through course)
- Policies correctly handle polymorphic relationships (sessions, teachers, recordings)
- Academy ownership is always validated, with super admins having context-aware access
- Status-based authorization ensures business logic constraints are enforced
- Parent-child relationships are validated through the pivot table

---

**Status:** ✅ All 6 policies implemented and registered successfully

**Date:** 2025-12-29

**Laravel Version:** 11.47.0
