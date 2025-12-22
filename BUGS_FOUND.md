# Bugs Found and Fixed

## Summary
- Total bugs found: 23
- Total bugs fixed: 21
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
- Status: In Progress (Level 1)
- Started: 326 errors
- Fixed: 67 errors
- Remaining: 259 errors
- Most remaining are relationship/architectural issues

### Phase 3: Database Check
- Status: Pending

### Phase 4: Route Check
- Status: Pending

### Phase 5: Crawl Application
- Status: Pending

### Phase 6: Critical Flows
- Status: Pending

### Phase 7: Check Logs
- Status: Pending
