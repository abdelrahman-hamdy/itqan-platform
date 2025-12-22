# Deferred Problems

This file tracks issues found during the bug audit that require discussion or significant changes before fixing.

## Summary
- Total deferred: 2

---

## 2. ScopedToAcademy Traits Used on Filament Resources

**Date Found:** 2025-12-22

**Files Affected:**
- `app/Filament/Resources/AcademicPackageResource.php`
- `app/Filament/Resources/AcademicSubjectResource.php`
- `app/Filament/Resources/AcademicTeacherProfileResource.php`
- `app/Filament/Resources/InteractiveCourseResource.php`
- `app/Filament/Resources/ParentProfileResource.php`
- `app/Filament/Resources/QuranCircleResource.php`
- `app/Filament/Resources/QuranPackageResource.php`
- `app/Filament/Resources/QuranSubscriptionResource.php`
- `app/Filament/Resources/QuranTrialRequestResource.php`
- `app/Filament/Resources/StudentProfileResource.php`
- `app/Filament/Resources/SupervisorProfileResource.php`

**Problem:**
The `ScopedToAcademy` and `ScopedToAcademyViaRelationship` traits are designed for Eloquent models (they use `static::addGlobalScope()`), but are being used on Filament Resources which don't have this method.

**Possible Solutions:**
1. Remove trait usage from Resources and apply scoping in `getEloquentQuery()` method instead
2. Create separate Resource-specific traits that override `getEloquentQuery()`
3. Apply scoping at the Model level only (which is already done)

**Current Status:** Trait usage removed from Resources to pass static analysis

**Action Required:** Verify academy scoping still works correctly after trait removal

---

## 1. QuranSessionReport Model Does Not Exist

**Date Found:** 2025-12-22

**Files Affected:**
- `app/Http/Controllers/Api/V1/Teacher/Quran/SessionController.php` (line ~356)
- `app/Http/Controllers/Api/V1/Teacher/StudentController.php` (line ~267)

**Problem:**
Controllers reference `App\Models\QuranSessionReport` which doesn't exist. They try to create/update reports with fields:
- `quran_session_id`
- `teacher_feedback`
- `memorization_rating`
- `tajweed_rating`
- `rating`
- `notes`

**Existing Model:**
`StudentSessionReport` exists with different fields:
- `session_id` (not `quran_session_id`)
- `student_id`, `teacher_id`, `academy_id`
- `notes` (could map to `teacher_feedback`)
- `new_memorization_degree`, `reservation_degree`
- No `memorization_rating`, `tajweed_rating`, or `rating` fields

**Possible Solutions:**
1. Update controllers to use `StudentSessionReport` with correct field mappings
2. Create a new `QuranSessionReport` model and migration
3. Add missing fields to `StudentSessionReport`

**Current Status:** Logic commented out to pass static analysis

**Action Required:** Decide on proper data model for Quran session evaluations

---
