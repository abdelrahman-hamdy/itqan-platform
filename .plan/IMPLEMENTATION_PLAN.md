# Itqan Platform - Full Implementation Plan

**Created:** 2025-12-06
**Status:** In Progress
**Total Phases:** 10
**Estimated Effort:** 5 Sprints (10 weeks)

---

## Progress Tracker

| Phase | Status | Started | Completed | Notes |
|-------|--------|---------|-----------|-------|
| Phase 1: Critical Security & Database | ⏳ Pending | - | - | - |
| Phase 2: Deprecated Code Removal | ⏳ Pending | - | - | - |
| Phase 3: Incomplete Implementations | ⏳ Pending | - | - | - |
| Phase 4: Architectural Improvements | ⏳ Pending | - | - | - |
| Phase 5: Authorization & Policies | ⏳ Pending | - | - | - |
| Phase 6: Performance Optimizations | ⏳ Pending | - | - | - |
| Phase 7: Frontend & Accessibility | ⏳ Pending | - | - | - |
| Phase 8: Testing Infrastructure | ⏳ Pending | - | - | - |
| Phase 9: Configuration Extraction | ⏳ Pending | - | - | - |
| Phase 10: Localization | ⏳ Pending | - | - | - |

**Legend:** ⏳ Pending | 🔄 In Progress | ✅ Completed | ⏸️ Blocked

---

## PHASE 1: Critical Security & Database Fixes

### 1.1 Security Vulnerabilities

#### 1.1.1 Add Authentication to File Upload Route
- **File:** `routes/web.php`
- **Line:** 1808
- **Issue:** `Route::post('/custom-file-upload', ...)` has no auth middleware
- **Fix:** Add `middleware('auth')` to route
- **Status:** ⏳ Pending

#### 1.1.2 Gate Debug Routes to Local Environment
- **File:** `routes/web.php`
- **Lines:** 852-908
- **Issue:** Debug endpoints exposed in production
- **Fix:** Wrap with `if (app()->environment('local', 'testing'))`
- **Status:** ⏳ Pending

#### 1.1.3 Add Rate Limiting to Webhook Endpoints
- **File:** `routes/web.php`
- **Lines:** 1759-1767
- **Issue:** No rate limiting on webhook routes
- **Fix:** Add `throttle:60,1` middleware
- **Status:** ⏳ Pending

#### 1.1.4 Remove Hardcoded API Key from JavaScript
- **File:** `resources/js/chat-enhanced.js`
- **Line:** 54
- **Issue:** Reverb API key fallback exposed
- **Fix:** Remove fallback, require proper env config
- **Status:** ⏳ Pending

#### 1.1.5 Add CSRF Protection to State-Changing Routes
- **Files:** `routes/web.php`
- **Lines:** 1001, 1104, 1156-1157, 1183, 1263
- **Issue:** POST routes outside middleware groups
- **Fix:** Ensure all within `web` middleware or explicit CSRF
- **Status:** ⏳ Pending

### 1.2 Database Integrity Fixes

#### 1.2.1 Fix Duplicate Migration Timestamps
- **Files:**
  - `database/migrations/2025_11_10_000000_create_academy_settings_table.php`
  - `database/migrations/2025_11_10_000000_remove_academic_status_and_graduation_date_from_student_profiles.php`
- **Issue:** Same timestamp causes ordering issues
- **Fix:** Rename second to `2025_11_10_000001_*`
- **Status:** ⏳ Pending

#### 1.2.2 Add Foreign Key Constraints to Teacher Tables
- **Files:**
  - `database/migrations/2025_12_03_163824_create_teacher_earnings_table.php`
  - `database/migrations/2025_12_03_163858_create_teacher_payouts_table.php`
- **Issue:** No FK on teacher_id column
- **Fix:** Create new migration to add constraints
- **Status:** ⏳ Pending

#### 1.2.3 Add tenant_id to Teacher Earnings/Payouts
- **Tables:** `teacher_earnings`, `teacher_payouts`
- **Issue:** Missing multi-tenancy column
- **Fix:** Create migration to add tenant_id with index
- **Status:** ⏳ Pending

#### 1.2.4 Consolidate academy_settings Migrations
- **Files:**
  - `2025_11_10_000000_create_academy_settings_table.php`
  - `2025_11_10_062604_create_academy_settings_table.php`
- **Issue:** Duplicate table creation
- **Fix:** Keep only 062604 version (has proper unique constraint)
- **Status:** ⏳ Pending

#### 1.2.5 Add Soft Deletes to 30 Models
- **Models requiring soft deletes:**
  ```
  GradeLevel, BusinessServiceCategory, MeetingAttendanceEvent,
  InteractiveSessionAttendance, InteractiveCourse, StudentSessionReport,
  SessionRequest, QuranSessionHomework, QuranSessionAttendance,
  AcademicSubject, StudentProfile, AcademicSessionAttendance,
  QuranSubscription, AcademicSessionReport, Academy,
  QuranTeacherProfile, QuizAssignment, HomeworkSubmission,
  PaymentWebhookEvent, AcademicTeacherProfile, User,
  QuizAttempt, ParentProfile, InteractiveSessionReport,
  AcademicHomeworkSubmission, InteractiveCourseSession
  ```
- **Fix:** Create migration to add deleted_at columns
- **Status:** ⏳ Pending

#### 1.2.6 Add Missing Database Indexes
- **Indexes to create:**
  ```sql
  teacher_earnings(teacher_type, teacher_id)
  teacher_payouts(teacher_type, teacher_id)
  academic_sessions(academic_subscription_id, status)
  payment_audit_logs(user_id, created_at)
  ```
- **Status:** ⏳ Pending

---

## PHASE 2: Deprecated Code Removal

### 2.1 Remove Deprecated Services

#### 2.1.1 Delete QuranAttendanceService
- **File:** `app/Services/QuranAttendanceService.php`
- **Replacement:** `app/Services/Attendance/QuranReportService.php`
- **Action:** Delete file, update any remaining references
- **Status:** ⏳ Pending

#### 2.1.2 Delete AcademicAttendanceService
- **File:** `app/Services/AcademicAttendanceService.php`
- **Replacement:** `app/Services/Attendance/AcademicReportService.php`
- **Action:** Delete file, update any remaining references
- **Status:** ⏳ Pending

### 2.2 Remove Deprecated Model Fields

#### 2.2.1 Clean QuranSession Deprecated Fields
- **File:** `app/Models/QuranSession.php`
- **Fields to remove references:**
  - `current_face` (Lines 680-681)
  - `papers_memorized_today` (Line 682)
  - `current_verse` (Lines 694, 1330)
  - `verses_memorized_today` (Lines 699, 738, 749)
  - `location_type` (Lines 600, 872)
  - `generated_from_schedule_id` (Lines 158-161)
- **Action:** Remove accessor methods and relationship referencing these fields
- **Status:** ⏳ Pending

### 2.3 Remove Redundant Relationships

#### 2.3.1 Consolidate QuranCircle Teacher Relationships
- **File:** `app/Models/QuranCircle.php`
- **Lines:** 107-149
- **Keep:** `quranTeacher()` (Line 123)
- **Remove:** `teacher()`, `teacherUser()`, `circleTeacher()`, `teacherProfile()`, `getQuranTeacherAttribute()`
- **Status:** ⏳ Pending

### 2.4 Remove Orphaned Views

#### 2.4.1 Delete Unused View Files
- **Files to delete:**
  - `resources/views/auth/login-old.blade.php`
- **Status:** ⏳ Pending

### 2.5 Remove TODO Comments for Excluded Features

#### 2.5.1 Remove TapGateway/MoyasarGateway TODOs
- **File:** `app/Services/Payment/PaymentGatewayManager.php`
- **Lines:** 44, 53
- **Action:** Remove TODO comments, add note that Paymob is the only supported gateway
- **Status:** ⏳ Pending

---

## PHASE 3: Incomplete Implementations

### 3.1 Critical TODO Implementations

#### 3.1.1 Implement Teacher Payout Notifications
- **File:** `app/Services/PayoutService.php`
- **Lines:** 193, 235, 274
- **Action:** Implement via NotificationService
- **Status:** ⏳ Pending

#### 3.1.2 Implement Subscription Renewal Notifications
- **File:** `app/Services/SubscriptionRenewalService.php`
- **Lines:** 408, 434, 459
- **Action:** Implement renewal reminders and expiration notifications
- **Status:** ⏳ Pending

#### 3.1.3 Implement LiveKit Recording Cleanup
- **File:** `app/Services/RecordingService.php`
- **Line:** 274
- **Action:** Implement file deletion on LiveKit server
- **Status:** ⏳ Pending

#### 3.1.4 Complete WireChat Integration in Sessions
- **Files:**
  - `resources/views/teacher/interactive-course-sessions/show.blade.php` (Line 256)
  - `resources/views/teacher/academic-sessions/show.blade.php` (Line 205)
- **Action:** Replace alert() placeholders with WireChat integration
- **Status:** ⏳ Pending

#### 3.1.5 Complete loadMoreMessages() Implementation
- **File:** `resources/js/chat-enhanced.js`
- **Line:** 1050-1051
- **Action:** Implement pagination for loading older messages
- **Status:** ⏳ Pending

### 3.2 Missing Filament ViewPages

#### 3.2.1 Create ViewPages for Resources
- **Resources needing ViewPage:**
  - `AcademicSessionResource`
  - `AcademicSessionReportResource`
  - `AcademicSubscriptionResource`
  - `HomeworkSubmissionResource`
  - `InteractiveCourseResource`
  - `InteractiveSessionReportResource`
  - `MeetingAttendanceResource`
  - `StudentProgressResource`
- **Status:** ⏳ Pending

### 3.3 Missing RelationManagers

#### 3.3.1 Add RelationManagers to Resources
- **QuranCircleResource:** Sessions, Students
- **AcademicSessionResource:** Reports, Attendance
- **InteractiveCourseResource:** Sessions, Enrollments
- **StudentProfileResource:** Subscriptions, Sessions
- **Status:** ⏳ Pending

### 3.4 Missing Model Observers

#### 3.4.1 Create InteractiveCourseSessionObserver
- **Events:** Status transitions, recording finalization, attendance finalization
- **Status:** ⏳ Pending

#### 3.4.2 Create ParentProfileObserver
- **Events:** Cascade delete prevention (orphan students)
- **Status:** ⏳ Pending

#### 3.4.3 Create StudentProfileObserver Enhancement
- **Events:** Grade level change validation
- **Status:** ⏳ Pending

#### 3.4.4 Create AcademyObserver
- **Events:** Tenant cleanup on deletion
- **Status:** ⏳ Pending

---

## PHASE 4: Architectural Improvements

### 4.1 Break Circular Dependencies

#### 4.1.1 Refactor SessionStatusService ↔ MeetingAttendanceService
- **Files:**
  - `app/Services/SessionStatusService.php`
  - `app/Services/MeetingAttendanceService.php`
- **Current Issue:** 26 `app()` service locator calls
- **Solution:**
  1. Create `SessionEventService` for shared logic
  2. Use Laravel Events to decouple
  3. Replace service locator with constructor injection
- **Status:** ⏳ Pending

### 4.2 Split Oversized Services

#### 4.2.1 Split CalendarService (787 lines)
- **Current:** `app/Services/CalendarService.php`
- **Split into:**
  - `app/Services/Calendar/QuranCalendarService.php`
  - `app/Services/Calendar/AcademicCalendarService.php`
  - `app/Services/Calendar/CourseCalendarService.php`
  - `app/Services/Calendar/CalendarFormatter.php`
- **Status:** ⏳ Pending

#### 4.2.2 Split SessionStatusService (780 lines)
- **Current:** `app/Services/SessionStatusService.php`
- **Extract:**
  - `app/Services/Session/StatusTransitionHandler.php`
  - `app/Services/Session/SessionNotificationHandler.php`
  - `app/Services/Session/AttendanceFinalizationHandler.php`
- **Status:** ⏳ Pending

#### 4.2.3 Split CertificateService (753 lines)
- **Current:** `app/Services/CertificateService.php`
- **Extract:**
  - `app/Services/Certificate/CertificateGenerator.php`
  - `app/Services/Certificate/CertificateValidator.php`
  - `app/Services/Certificate/CertificateNotifier.php`
- **Status:** ⏳ Pending

### 4.3 Create Service Interfaces

#### 4.3.1 Create Core Interfaces
- **Files to create:**
  - `app/Contracts/CalendarInterface.php`
  - `app/Contracts/NotificationDispatcherInterface.php`
  - `app/Contracts/VideoConferencingInterface.php`
  - `app/Contracts/PaymentOrchestrationInterface.php`
- **Status:** ⏳ Pending

### 4.4 Consolidate Duplicate Services

#### 4.4.1 Merge Meeting Services
- **Current:**
  - `SessionMeetingService.php`
  - `AcademicSessionMeetingService.php`
- **Action:** Consolidate into single service with strategy pattern
- **Status:** ⏳ Pending

---

## PHASE 5: Authorization & Policies

### 5.1 Create Missing Policies

#### 5.1.1 Create Core Policies
- **Files to create:**
  ```
  app/Policies/AcademyPolicy.php
  app/Policies/SessionPolicy.php
  app/Policies/SubscriptionPolicy.php
  app/Policies/AttendancePolicy.php
  app/Policies/HomeworkPolicy.php
  app/Policies/TeacherProfilePolicy.php
  app/Policies/StudentProfilePolicy.php
  app/Policies/MeetingPolicy.php
  app/Policies/PaymentPolicy.php
  ```
- **Status:** ⏳ Pending

### 5.2 Add Authorization to Filament Resources

#### 5.2.1 Implement Resource Authorization
- **Priority Resources:**
  - `AdminResource`
  - `UserResource`
  - `AcademyManagementResource`
  - `PaymentResource`
  - `BusinessServiceRequestResource`
- **Action:** Add `canCreate()`, `canEdit()`, `canDelete()`, `canView()` methods
- **Status:** ⏳ Pending

### 5.3 Fix Route Authorization

#### 5.3.1 Add Role Middleware to Routes
- **Routes to fix:**
  - `/payments/{payment}/refund` - Add `middleware('role:admin,teacher')`
  - `/courses/{id}/enroll` - Add email verification check
  - Trial request submission - Add `verified` middleware
- **Status:** ⏳ Pending

---

## PHASE 6: Performance Optimizations

### 6.1 Fix N+1 Query Issues

#### 6.1.1 Add Eager Loading to Models
- **Models to fix:**
  - `BaseSession` - Add `$with` property
  - `QuranSession` - Add eager loading defaults
  - `InteractiveCourse` - Add eager loading defaults
  - `QuranCircle` - Fix redundant teacher lookups
- **Status:** ⏳ Pending

#### 6.1.2 Add Eager Loading to Filament Resources
- **Resources to fix:**
  - `AcademicSessionResource` (Lines 183, 187, 194)
  - `InteractiveCourseResource` (Line 396)
  - `QuranCircleResource` (Lines 317, 359)
  - `StudentProfileResource`
  - `ParentProfileResource`
- **Action:** Add `getEloquentQuery()` with proper `->with()` calls
- **Status:** ⏳ Pending

### 6.2 Implement Cache Invalidation

#### 6.2.1 Add Cache Tags to CalendarService
- **File:** `app/Services/CalendarService.php`
- **Action:**
  - Add cache tags for session types
  - Create cache invalidation observers
  - Use event-driven cache busting
- **Status:** ⏳ Pending

---

## PHASE 7: Frontend & Accessibility

### 7.1 Add Loading States

#### 7.1.1 Create Loading Component
- **File to create:** `resources/views/components/ui/loading-spinner.blade.php`
- **Apply to:** 50+ views without loading indicators
- **Status:** ⏳ Pending

### 7.2 Fix Accessibility Violations

#### 7.2.1 Add Alt Text to Images
- **Files to fix:**
  - `resources/views/chat/unified.blade.php`
  - `resources/views/components/academy-logo.blade.php`
  - `resources/views/components/avatar.blade.php`
  - `resources/views/components/student-avatar.blade.php`
  - `resources/views/components/teacher-avatar.blade.php`
  - `resources/views/components/profile/picture-upload.blade.php`
  - `resources/views/components/interactive/session-info-sidebar.blade.php`
  - `resources/views/components/certificate-card.blade.php`
  - `resources/views/livewire/issue-certificate-modal.blade.php`
- **Status:** ⏳ Pending

#### 7.2.2 Add ARIA Attributes
- **Target:** 74 views missing proper ARIA labels
- **Action:** Add role, aria-label, aria-describedby attributes
- **Status:** ⏳ Pending

### 7.3 Fix RTL Support

#### 7.3.1 Replace LTR-Only CSS Classes
- **Issue:** 55+ instances of `pr-*`, `pl-*`, `left-*`, `right-*`
- **Fix:** Use RTL-safe alternatives or add `rtl:` variants
- **Status:** ⏳ Pending

### 7.4 Remove Production Debug Code

#### 7.4.1 Remove Console Logs
- **Files to clean:**
  - `resources/js/app.js` (4 instances)
  - `resources/js/chat-enhanced.js` (20+ instances)
  - `resources/js/components/tabs.js` (10+ instances)
- **Status:** ⏳ Pending

---

## PHASE 8: Testing Infrastructure

### 8.1 Create Service Tests

#### 8.1.1 Create Unit Tests for Critical Services
- **Tests to create:**
  ```
  tests/Unit/Services/SessionStatusServiceTest.php
  tests/Unit/Services/CalendarServiceTest.php
  tests/Unit/Services/PaymentServiceTest.php
  tests/Unit/Services/NotificationServiceTest.php
  tests/Unit/Services/SubscriptionRenewalServiceTest.php
  tests/Unit/Services/MeetingAttendanceServiceTest.php
  tests/Unit/Services/LiveKitServiceTest.php
  tests/Unit/Services/UnifiedAttendanceServiceTest.php
  ```
- **Status:** ⏳ Pending

### 8.2 Create Feature Tests

#### 8.2.1 Create Filament Resource Tests
- **Target:** Test CRUD operations for all resources
- **Status:** ⏳ Pending

#### 8.2.2 Create API Endpoint Tests
- **Target:** Test all API routes
- **Status:** ⏳ Pending

---

## PHASE 9: Configuration Extraction

### 9.1 Extract Magic Numbers

#### 9.1.1 Create Session Config
- **File to create:** `config/sessions.php`
- **Values to extract:**
  - Early join grace period (15 min)
  - Max future hours for ongoing (2 hours)
  - Scheduler safety window (24 hours)
- **Status:** ⏳ Pending

#### 9.1.2 Create Meeting Config
- **File to create:** `config/meetings.php`
- **Values to extract:**
  - Message TTL (3600)
  - Permission cache TTL (86400)
  - Max retry attempts (3)
- **Status:** ⏳ Pending

---

## PHASE 10: Localization

### 10.1 Extract Hardcoded Strings

#### 10.1.1 Create Language Files
- **Files to create/update:**
  - `lang/ar/sessions.php`
  - `lang/ar/courses.php`
  - `lang/ar/subscriptions.php`
  - `lang/ar/teachers.php`
- **Target:** 100+ hardcoded Arabic strings
- **Status:** ⏳ Pending

#### 10.1.2 Localize JavaScript Strings
- **File:** `resources/js/chat-enhanced.js`
- **Action:** Create window.translations object for JS strings
- **Status:** ⏳ Pending

---

## Implementation Log

### Session 1: 2025-12-06
- [x] Initial comprehensive audit completed
- [x] Plan document created
- [ ] GitHub backup created
- [ ] Phase 1 implementation started

---

## Notes

### Excluded Features (Per User Request)
- ❌ User notification preferences (NotificationService.php:446)
- ❌ TapGateway support (PaymentGatewayManager.php:44)
- ❌ MoyasarGateway support (PaymentGatewayManager.php:53)
- ✅ Paymob remains the only payment gateway

### Dependencies
- Phase 2 depends on Phase 1 (database integrity first)
- Phase 4 depends on Phase 3 (complete features before refactoring)
- Phase 8 depends on Phases 1-7 (test stable code)

### Risk Mitigation
- Always create database backups before migrations
- Test migrations on staging before production
- Keep deprecated code backup for 30 days after removal
