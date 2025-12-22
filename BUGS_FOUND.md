# Bugs Found and Fixed

## Summary
- Total bugs found: 25
- Total bugs fixed: 23
- Deferred (need discussion): 2 (see DEFERRED_PROBLEMS.md)
- PHPStan Level 1 errors remaining: 259 (mostly missing relationships on models)

## Fixed Bugs

### 2025-12-22 - Removed deprecated TestCronJobsCommand
- **File:** `app/Console/Commands/TestCronJobsCommand.php`
- **Error:** Referenced non-existent Jobs (PrepareUpcomingSessions, GenerateWeeklyScheduleSessions, CleanupExpiredTokens) and GoogleToken model
- **Fix:** Deleted the deprecated command
- **Commit:** f469472

### 2025-12-22 - Removed deprecated controllers and orphaned resources
- **Files:**
  - `app/Http/Controllers/QuranHomeworkController.php`
  - `app/Http/Controllers/QuranSubscriptionController.php`
  - `app/Http/Controllers/QuranTeacherController.php`
  - `app/Filament/Resources/AcademyDesignResource/Pages/*`
- **Error:** Referenced non-existent models and not used in routes
- **Fix:** Deleted deprecated files
- **Commit:** 6659a06

### 2025-12-22 - Fixed references to non-existent models/services
- **Files:** Multiple (8 files)
- **Error:**
  - UnifiedAttendanceService -> MeetingAttendanceService
  - AcademicTeacher -> AcademicTeacherProfile
  - Invoice relationship removed (model doesn't exist)
  - InteractiveTeacherPayment relationship removed
  - AssignmentSubmission relationship removed
  - TeachingSession relationship removed
- **Fix:** Updated to use correct existing models/services
- **Commit:** 415d549

### 2025-12-22 - Fixed FileUpload namespace in CreateRecordedCourse
- **File:** `app/Filament/Academy/Resources/RecordedCourseResource/Pages/CreateRecordedCourse.php`
- **Error:** Used wrong namespace `Forms\Components\FileUpload` instead of imported `FileUpload`
- **Fix:** Changed to use imported class directly
- **Commit:** ceff4b6

### 2025-12-22 - Fixed Payout -> TeacherPayout
- **File:** `app/Http/Controllers/Api/V1/Teacher/EarningsController.php`
- **Error:** Referenced non-existent `Payout` model
- **Fix:** Changed to `TeacherPayout`
- **Commit:** ceff4b6

### 2025-12-22 - Removed non-existent ViewAcademicSession page
- **File:** `app/Filament/Resources/AcademicSessionResource.php`
- **Error:** Referenced non-existent page class in getPages()
- **Fix:** Removed view route from pages array
- **Commit:** 0574a48

### 2025-12-22 - Fixed IconColumn -> IconEntry in Infolist
- **File:** `app/Filament/Resources/StudentProgressResource/Pages/ViewStudentProgress.php`
- **Error:** Used IconColumn instead of IconEntry for Filament Infolists
- **Fix:** Changed to IconEntry
- **Commit:** 0574a48

### 2025-12-22 - Fixed AcademicTeacher -> AcademicTeacherProfile
- **File:** `app/Http/Controllers/StudentProfileController.php`
- **Error:** Referenced non-existent `AcademicTeacher` model
- **Fix:** Changed to `AcademicTeacherProfile`
- **Commit:** 0574a48

### 2025-12-22 - Removed incorrect trait usage from Filament Resources
- **Files:** 11 Filament Resource files
- **Error:** ScopedToAcademy/ScopedToAcademyViaRelationship traits use addGlobalScope() which is an Eloquent method not available on Resources
- **Fix:** Removed trait usage from Resources
- **Commit:** 7fd4ef5

### 2025-12-22 - Fixed undefined $roomName variable
- **File:** `app/Services/LiveKitService.php`
- **Error:** $roomName could be undefined in catch block
- **Fix:** Moved roomName generation before try block
- **Commit:** e1e113b

### 2025-12-22 - Fixed seeder schema mismatches (Phase 3)
- **Files:**
  - `app/Models/QuranTeacherProfile.php`
  - `app/Models/AcademicTeacherProfile.php`
  - `database/seeders/ComprehensiveDataSeeder.php`
- **Errors Fixed:**
  1. Duplicate teacher_code - `mt_rand(0,5)` caused non-deterministic codes
  2. StudentProfile seeder creating duplicates (User boot() already creates profiles)
  3. RecordedCourse seeder using non-existent columns
  4. InteractiveCourse missing total_sessions calculation
  5. QuranCircle using invalid session_duration_minutes column
  6. QuranSubscription using wrong column names
- **Fix:** Updated generateTeacherCode to use `withoutGlobalScopes()`, fixed seeder column mappings
- **Commit:** 6f6e3cb

### 2025-12-22 - Fixed duplicate route names (Phase 4)
- **File:** `routes/web.php`
- **Error:** Duplicate route names `teacher.academic.lessons.index` and `teacher.academic.lessons.show`
  - Defined in both `routes/web.php` and `routes/auth.php`
  - Different URIs: `/teacher/academic-lessons` vs `/teacher/academic/lessons`
- **Fix:** Removed duplicate routes from web.php, keeping more complete routes in auth.php

## Remaining PHPStan Level 1 Issues (259 errors)

Most remaining errors fall into these categories:
1. **Missing relationships on models** (e.g., 'quiz' on QuizAttempt, 'course' on CourseSubscription)
2. **Undefined $form property** on Filament Pages (need to add Livewire form trait)
3. **Unused closure variables** (cosmetic, not runtime errors)
4. **WireChat migration command** using undefined constants (deprecated command)
5. **PHP 8.4 deprecation warnings** (nullable parameter issues)

These require either:
- Adding missing relationships to models
- Architectural changes to Filament pages
- Removing deprecated migration commands

## Phase Progress

### Phase 1: Setup Tools
- Status: Completed
- Installed: Larastan, Laravel Telescope
- Skipped: spatie/laravel-link-checker (incompatible with PHP 8.4/Laravel 11)

### Phase 2: Static Analysis (PHPStan)
- Status: Completed (Level 1)
- Started: 326 errors
- Fixed: 67 errors
- Remaining: 259 errors (mostly relationship/architectural issues - deferred)

### Phase 3: Database Check
- Status: Completed
- Command: `php artisan migrate:fresh --seed`
- Fixed: 6 seeder schema mismatches
- Result: Database seeding successful

### Phase 4: Route Check
- Status: Completed
- Command: `php artisan route:list --json`
- Checked: Duplicate route names, missing controllers
- Fixed: 1 duplicate route name issue
- Result: All routes valid, `route:cache` succeeds

### Phase 5: Crawl Application
- Status: Pending

### Phase 6: Critical Flows
- Status: Pending

### Phase 7: Check Logs
- Status: Pending
