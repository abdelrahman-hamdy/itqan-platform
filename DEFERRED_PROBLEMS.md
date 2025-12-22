# Deferred Problems

This file tracks issues found during the bug audit that require discussion or significant changes before fixing.

## Summary
- Total deferred: 1

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
