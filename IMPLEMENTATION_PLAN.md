# Itqan Platform - Comprehensive Feature Completion Plan

> **Project Phase**: Development
> **Priority System**: 1-10 (10 = Critical, 1 = Low)
> **Estimated Timeline**: 8-10 weeks
> **Last Updated**: 2025-11-10

---

## Executive Summary

This document outlines a strategic, phased implementation plan to complete all pending features of the Itqan Educational Platform. The plan addresses 12 major feature areas across 4 implementation phases, prioritized by business impact and technical dependencies.

### Critical Success Factors
1. ✅ **Zero Breaking Changes** - All existing features must continue working
2. ✅ **Unified Architecture** - Common patterns across all educational sections
3. ✅ **Scalable Design** - Support multi-tenancy and future growth
4. ✅ **Performance First** - Optimized queries and real-time capabilities

---

## Table of Contents

1. [Current State Analysis](#current-state-analysis)
2. [Architecture Decisions](#architecture-decisions)
3. [Implementation Phases](#implementation-phases)
4. [Database Schema Changes](#database-schema-changes)
5. [Service Layer Architecture](#service-layer-architecture)
6. [UI/UX Implementation](#uiux-implementation)
7. [Testing Strategy](#testing-strategy)
8. [Deployment Plan](#deployment-plan)

---

## Current State Analysis

### Completed Features (75% Complete)
- ✅ Quran Group Circles (Full CRUD, Scheduling, Progress tracking)
- ✅ Quran Individual Circles (Subscriptions, Sessions, Homework)
- ✅ Recorded Courses (Content management, Enrollment)
- ✅ Multi-tenant architecture with Academy isolation
- ✅ User authentication and role-based access
- ✅ Filament admin panels (5 panels operational)
- ✅ Basic chat/messaging system (Chatify integration)

### Incomplete Features (25% Remaining)
- ⚠️ Interactive Courses (70% - Missing: Homework, Payment, Teacher compensation)
- ⚠️ Individual Academic Sessions (40% - Missing: Student booking flow, Scheduling)
- ❌ Unified Attendance System (0% - Scattered across multiple tables)
- ❌ Unified Meeting System (0% - Multiple meeting configs, no LiveKit)
- ❌ Comprehensive Reports (30% - Basic reports exist, missing unified structure)
- ❌ Parent Dashboard (10% - Placeholder views only)
- ❌ Homework Submission UI (20% - Models exist, no student/teacher UI)
- ❌ Quiz System (0% - Not implemented)
- ❌ Certificate System (0% - Not implemented)
- ❌ Teacher Availability Calendar (50% - Display only, no booking integration)

---

## Architecture Decisions

### 1. Meeting System Architecture

**Decision**: Single unified `Meeting` model with polymorphic relationships

```php
// New unified structure
meetings
├── id
├── meetable_type (QuranSession, AcademicSession, InteractiveCourseSession)
├── meetable_id
├── livekit_room_name
├── livekit_room_id
├── access_token
├── status (scheduled, active, ended, cancelled)
├── scheduled_start_at
├── actual_start_at
├── actual_end_at
├── recording_enabled
├── recording_url
└── metadata (JSON for platform-specific config)
```

**Benefits**:
- Single source of truth for all meetings
- Simplified LiveKit integration
- Unified attendance tracking
- Easier maintenance

### 2. Attendance System Architecture

**Decision**: Keep separate attendance tables but unify calculation logic

```php
// Service-based unified logic
UnifiedAttendanceService
├── calculateAttendance(Meeting $meeting, User $user)
├── autoTrackFromMeeting(Meeting $meeting)
├── manualOverride(Attendance $attendance, array $data)
└── getAttendanceReport(Session $session, User $user)

// Separate tables remain
quran_session_attendances
academic_session_attendances  // New
interactive_session_attendances  // New
```

**Benefits**:
- Preserves existing data structure
- Unified business logic through service layer
- Section-specific fields (e.g., tajweed_score for Quran)
- Flexible for future customization

### 3. Report System Architecture

**Decision**: Polymorphic report system with section-specific data

```php
// Unified report structure
session_reports
├── id
├── reportable_type (QuranSession, AcademicSession, etc.)
├── reportable_id
├── student_id
├── teacher_id
├── attendance_data (JSON)
├── homework_data (JSON - nullable)
├── performance_data (JSON - section-specific)
├── teacher_notes
├── overall_score
└── report_period (session, monthly, overall)
```

**Benefits**:
- Flexible JSON fields for section-specific metrics
- Single query to fetch all report types
- Unified access control
- Easy to extend for new sections

### 4. Configuration Management

**Decision**: Three-level configuration hierarchy

```
Global Defaults (Code-level constants)
    ↓
Academy Settings (Database - academy_settings table)
    ↓
Entity Settings (Circle/Course specific - embedded in entity)
```

**Example**:
```php
// Usage
$attendanceThreshold = $circle->attendance_threshold
    ?? $academy->settings['attendance_threshold']
    ?? config('itqan.default_attendance_threshold');
```

---

## Implementation Phases

### **Phase 1: Foundation & Critical Features** (Weeks 1-3) ✅ **COMPLETED**
**Priority**: 10 | **Dependencies**: None
**Status**: All 3 sub-phases completed on 2025-11-10

#### 1.1 Unified Meeting System with LiveKit
**Timeline**: Week 1 (5-7 days) ✅ **COMPLETED**

**Tasks**:
- [x] Create `Meeting` model with polymorphic relationships
- [x] Integrate LiveKit SDK (server-side token generation)
- [x] Create `MeetingService` for room management
- [x] Implement real-time participant tracking
- [x] Build unified meeting UI component (LiveKit client SDK)
- [x] Migrate existing meeting configs to new structure
- [x] Test with Quran sessions (existing feature)

**Deliverables**:
- `app/Models/Meeting.php`
- `app/Services/MeetingService.php`
- `database/migrations/2025_11_11_create_meetings_table.php`
- `resources/views/components/meetings/livekit-room.blade.php`
- LiveKit configuration in `.env.example`

**Files to Modify**:
- `app/Models/QuranSession.php` - Add meeting relationship
- `app/Models/AcademicSession.php` - Add meeting relationship
- `app/Models/InteractiveCourseSession.php` - Add meeting relationship

---

#### 1.2 Unified Attendance System
**Timeline**: Week 2 (7-8 days) ✅ **COMPLETED**

**Tasks**:
- [x] Create `UnifiedAttendanceService` with common logic
- [x] Add `academic_session_attendances` table (mirror quran structure)
- [x] Add `interactive_session_attendances` table
- [x] Implement auto-attendance from LiveKit participant events
- [x] Create manual override functionality for teachers
- [x] Add configurable thresholds (late_tolerance, attendance_threshold)
- [x] Build attendance UI component (teacher manual override)
- [x] Implement real-time attendance sync (WebSockets/Pusher)

**Deliverables**:
- `app/Services/UnifiedAttendanceService.php`
- `database/migrations/2025_11_12_create_academic_session_attendances_table.php`
- `database/migrations/2025_11_12_create_interactive_session_attendances_table.php`
- `database/migrations/2025_11_12_add_attendance_settings_to_entities.php`
- `resources/views/components/attendance/attendance-tracker.blade.php`
- `resources/views/components/attendance/manual-override-modal.blade.php`

**Configuration Fields to Add**:
```php
// Add to quran_circles, academic_classes, interactive_courses
preparation_minutes (default: 15)
buffer_minutes (default: 5)
late_tolerance_minutes (default: 10)
attendance_threshold_minutes (default: 80% of session duration)
```

---

#### 1.3 Configuration Enums & Settings
**Timeline**: Week 2 (2-3 days, parallel with 1.2) ✅ **COMPLETED**

**Tasks**:
- [x] Create `SessionDuration` enum (30, 60, 90 minutes)
- [x] Create `AcademySettings` model/table
- [x] Add academy-level default configurations
- [x] Replace all hardcoded durations across codebase
- [x] Add Filament resource for academy settings management
- [x] Update existing migrations to use enum

**Deliverables**:
- `app/Enums/SessionDuration.php`
- `app/Models/AcademySettings.php`
- `database/migrations/2025_11_13_create_academy_settings_table.php`
- `database/migrations/2025_11_13_update_session_durations_to_enum.php`
- `app/Filament/Resources/AcademySettingsResource.php`

**Enum Definition**:
```php
enum SessionDuration: int
{
    case THIRTY = 30;
    case SIXTY = 60;
    case NINETY = 90;

    public function label(): string
    {
        return match($this) {
            self::THIRTY => '30 دقيقة',
            self::SIXTY => 'ساعة واحدة',
            self::NINETY => 'ساعة ونصف',
        };
    }
}
```

---

### **Phase 2: Core Educational Features** (Weeks 3-5) 🔄 **IN PROGRESS**
**Priority**: 10 | **Dependencies**: Phase 1 completed
**Status**: Phase 2.1 - ✅ **COMPLETE** (100% - Payment deferred to deployment)

#### 2.1 Complete Interactive Courses Feature
**Timeline**: Week 3-4 (8-10 days) ✅ **COMPLETED**

**Tasks**:
- [x] Add homework support to `InteractiveCourseSession`
- [x] Create `InteractiveCourseHomework` model
- [x] Prepare enrollment flow (payment deferred to pre-deployment)
- [x] Create teacher payment calculation system
- [x] Add payment type config (fixed, per_student, per_session)
- [x] Build student enrollment UI (browse → enroll → pay flow ready)
- [x] Verify teacher course management in Filament (already exists)
- [x] Session scheduling via calendar (already integrated)
- [x] Integrate with unified Meeting and Attendance systems
- [x] Create course progress tracking

**Deliverables**:
- `app/Models/InteractiveCourseHomework.php`
- `app/Services/InteractiveCoursePaymentService.php`
- `database/migrations/2025_11_14_add_homework_to_interactive_courses.php`
- `database/migrations/2025_11_15_add_payment_config_to_interactive_courses.php`
- `resources/views/student/interactive-courses/enroll.blade.php`
- `resources/views/teacher/interactive-courses/manage.blade.php`
- `app/Filament/Resources/InteractiveCourseResource.php` (update)

**Payment Calculation Logic**:
```php
class InteractiveCoursePaymentService
{
    public function calculateTeacherPayout(InteractiveCourse $course): float
    {
        return match($course->payment_type) {
            'fixed' => $course->teacher_fixed_amount,
            'per_student' => $course->enrollments_count * $course->amount_per_student,
            'per_session' => $course->sessions_count * $course->amount_per_session,
        };
    }
}
```

---

#### 2.2 Individual Academic Session Booking Flow ✅ **COMPLETED**
**Timeline**: Week 4-5 (7-9 days)

**Tasks**:
- [x] Create academic teacher public browsing page
- [x] Build package selection UI for students
- [x] Create subscription checkout flow (payment deferred)
- [x] Implement teacher notification system (email + in-app) - **DEFERRED to Phase 5**
- [x] Add teacher session scheduling interface in Filament
- [x] Create session calendar for teachers (drag-drop scheduling)
- [x] Build student subscription management page
- [x] Add payment processing for academic subscriptions - **DEFERRED to deployment**
- [x] Integrate with unified Meeting system
- [x] Add progress tracking for academic sessions

**Deliverables**:
- [x] `app/Http/Controllers/AcademicTeacherController.php` (update) ✅
- [x] `resources/views/public/academic-teachers/index.blade.php` ✅
- [x] `resources/views/public/academic-teachers/show.blade.php` ✅
- [x] `resources/views/student/academic-subscription-detail.blade.php` ✅
- [x] `resources/views/student/academic-session-detail.blade.php` ✅
- [x] `app/Filament/AcademicTeacher/Resources/AcademicSessionResource.php` (update) ✅
- [x] `app/Services/SessionManagementService.php` ✅
- [ ] `app/Notifications/NewAcademicSubscription.php` - **DEFERRED to Phase 5**

**Booking Flow**:
```
1. Student browses /academic-teachers
2. Filters by subject, grade, availability
3. Clicks teacher → views profile & packages
4. Selects package → fills form (goals, preferred times)
5. Payment checkout
6. Teacher receives notification
7. Teacher schedules sessions via Filament calendar
8. Student receives session notifications
```

---

#### 2.3 Homework Submission & Grading System ✅ **COMPLETED**
**Timeline**: Week 5 (6-7 days)

**Tasks**:
- [x] Create unified homework submission UI for students
- [x] Build file upload component (images, PDFs)
- [x] Add text answer support
- [x] Create teacher grading interface
- [x] Implement grading workflow (pending → graded → returned)
- [ ] Add homework notifications (assigned, graded) - **DEFERRED to Phase 5**
- [ ] Integrate homework with session reports - **DEFERRED to Phase 4 (Reporting)**
- [x] Add homework to Quran, Academic, Interactive sessions (Already exists)
- [x] Create homework listing for students (My Homework page)
- [x] Add homework statistics to teacher dashboard ✅
- [x] Add homework statistics to student dashboard ✅
- [x] Integrate homework navigation in student menus ✅

**Deliverables**:
- [x] `resources/views/student/homework/index.blade.php` ✅
- [x] `resources/views/student/homework/submit.blade.php` ✅
- [x] `resources/views/student/homework/view.blade.php` ✅
- [x] `resources/views/student/dashboard.blade.php` (with homework integration) ✅
- [x] `resources/views/teacher/homework/index.blade.php` ✅
- [x] `resources/views/teacher/homework/grade.blade.php` ✅
- [x] `resources/views/components/homework/submission-form.blade.php` ✅
- [x] `resources/views/components/homework/grading-interface.blade.php` ✅
- [x] `resources/views/components/navigation/student-nav.blade.php` (homework links) ✅
- [x] `resources/views/components/sidebar/student-sidebar.blade.php` (homework links) ✅
- [x] `app/Services/HomeworkService.php` ✅
- [ ] `app/Notifications/HomeworkAssigned.php` - **DEFERRED to Phase 5**
- [ ] `app/Notifications/HomeworkGraded.php` - **DEFERRED to Phase 5**
- [x] `app/Models/AcademicHomework.php` ✅
- [x] `app/Models/AcademicHomeworkSubmission.php` ✅
- [x] `database/migrations/2025_11_10_130000_create_academic_homework_table.php` ✅
- [x] `database/migrations/2025_11_10_130100_create_academic_homework_submissions_table.php` ✅
- [x] `app/Http/Controllers/Student/HomeworkController.php` ✅
- [x] `app/Http/Controllers/StudentDashboardController.php` (homework integration) ✅
- [x] `app/Http/Controllers/Teacher/HomeworkGradingController.php` ✅
- [x] Student homework routes (index, submit, view) ✅
- [x] Teacher homework grading routes (index, grade, statistics) ✅

**Homework Submission Component**:
```php
<livewire:homework-submission
    :homework="$homework"
    :student="$student"
/>

// Supports:
// - File uploads (multiple)
// - Text answer (rich text editor)
// - Late submission warnings
// - Submission history
```

---

### **Phase 3: Extended Features** (Weeks 5-7)
**Priority**: 5-9 | **Dependencies**: Phase 2 completed

#### 3.1 Parent Dashboard Suite
**Timeline**: Week 5-6 (8-10 days)

**Tasks**:
- [ ] Create parent dashboard home page
- [ ] Build children management interface
- [ ] Add session viewing (upcoming, past) per child
- [ ] Create progress monitoring dashboard
- [ ] Build payment management interface
- [ ] Add messaging with teachers (Chatify integration)
- [ ] Create subscription overview per child
- [ ] Add notification preferences
- [ ] Build report viewing interface
- [ ] Add multi-child support (switch between children)

**Deliverables**:
- `resources/views/parent/dashboard.blade.php`
- `resources/views/parent/children/index.blade.php`
- `resources/views/parent/children/sessions.blade.php`
- `resources/views/parent/children/progress.blade.php`
- `resources/views/parent/payments/index.blade.php`
- `resources/views/parent/reports/index.blade.php`
- `app/Http/Controllers/ParentDashboardController.php`
- `app/Http/Middleware/EnsureUserIsParent.php`

**Parent Dashboard Layout**:
```
┌─────────────────────────────────────┐
│ Parent Dashboard                    │
├─────────────────────────────────────┤
│ Children Selector: [Ahmed ▼]        │
├──────────────┬──────────────────────┤
│ Sidebar:     │ Main Content:        │
│ - Dashboard  │ - Upcoming Sessions  │
│ - Sessions   │ - Recent Activity    │
│ - Progress   │ - Payment Summary    │
│ - Reports    │ - Quick Actions      │
│ - Payments   │                      │
│ - Messages   │                      │
└──────────────┴──────────────────────┘
```

---

#### 3.2 Teacher Availability Calendar
**Timeline**: Week 6 (5-6 days)

**Tasks**:
- [ ] Create teacher availability management interface
- [ ] Build weekly availability grid (Filament widget)
- [ ] Add exception/vacation dates
- [ ] Create booking integration for students
- [ ] Add conflict detection algorithm
- [ ] Build calendar visualization for teachers
- [ ] Add bulk scheduling for recurring sessions
- [ ] Integrate with session scheduling flow
- [ ] Add availability notifications

**Deliverables**:
- `app/Models/TeacherAvailability.php`
- `database/migrations/2025_11_16_create_teacher_availabilities_table.php`
- `app/Filament/Teacher/Widgets/AvailabilityCalendar.php`
- `resources/views/components/calendar/availability-grid.blade.php`
- `app/Services/TeacherAvailabilityService.php`

**Availability Table Structure**:
```php
teacher_availabilities
├── id
├── teacher_id
├── day_of_week (0-6, Sunday-Saturday)
├── start_time
├── end_time
├── is_available (boolean)
├── exception_dates (JSON - array of dates)
└── notes
```

---

#### 3.3 Comprehensive Student Reports
**Timeline**: Week 6-7 (7-9 days)

**Tasks**:
- [ ] Create unified `SessionReport` polymorphic model
- [ ] Build section-specific report cards
- [ ] Add monthly aggregation reports
- [ ] Create overall performance dashboard
- [ ] Implement conditional field calculations (Quran memorization options)
- [ ] Build teacher report generation interface
- [ ] Add student report viewing pages
- [ ] Create parent report access
- [ ] Add report filtering (by date, section, performance)
- [ ] Implement report sharing (teacher → parent)

**Deliverables**:
- `app/Models/SessionReport.php`
- `database/migrations/2025_11_17_create_session_reports_table.php`
- `app/Services/ReportGenerationService.php`
- `resources/views/student/reports/index.blade.php`
- `resources/views/student/reports/quran.blade.php`
- `resources/views/student/reports/academic.blade.php`
- `resources/views/teacher/reports/generate.blade.php`
- `resources/views/components/reports/report-card.blade.php`

**Report Data Structure**:
```json
{
  "attendance": {
    "status": "present|late|absent",
    "percentage": 95.5,
    "join_time": "2025-11-10 10:05:00",
    "leave_time": "2025-11-10 11:00:00",
    "is_manual_override": false
  },
  "homework": {
    "assigned": true,
    "submitted": true,
    "grade": 8.5,
    "submission_date": "2025-11-09 18:30:00",
    "teacher_feedback": "..."
  },
  "performance": {
    // Quran-specific
    "new_memorization_degree": 9.0,
    "review_degree": 8.5,
    "comprehensive_review_degree": 8.0,
    "tajweed_score": 9.5,
    "enabled_fields": ["new_memorization", "review", "tajweed"],

    // Academic-specific (if applicable)
    "understanding_level": 8.0,
    "participation_score": 9.0
  }
}
```

---

### **Phase 4: Advanced Features** (Weeks 7-8)
**Priority**: 3-5 | **Dependencies**: Phase 3 completed

#### 4.1 Quiz & Assessment System
**Timeline**: Week 7 (7-8 days)

**Tasks**:
- [ ] Create `Quiz` polymorphic model (assignable to any session type)
- [ ] Build question bank system (MCQ, True/False, Essay)
- [ ] Add quiz creation interface for teachers (Filament)
- [ ] Create student quiz-taking interface
- [ ] Implement auto-grading for objective questions
- [ ] Add manual grading for essay questions
- [ ] Create quiz results dashboard
- [ ] Add quiz analytics (average score, pass rate)
- [ ] Integrate quiz scores with session reports
- [ ] Add quiz notifications (assigned, results)

**Deliverables**:
- `app/Models/Quiz.php`
- `app/Models/QuizQuestion.php`
- `app/Models/QuizAttempt.php`
- `database/migrations/2025_11_18_create_quizzes_table.php`
- `database/migrations/2025_11_18_create_quiz_questions_table.php`
- `database/migrations/2025_11_18_create_quiz_attempts_table.php`
- `app/Filament/Resources/QuizResource.php`
- `resources/views/student/quizzes/take.blade.php`
- `resources/views/teacher/quizzes/results.blade.php`

**Quiz Assignment Options**:
```php
// Option 1: Assign to specific session
$quiz->assignToSession($session);

// Option 2: Assign as standalone (like homework)
$quiz->assignToStudents($students, $dueDate);

// Option 3: Assign to entire circle/course
$quiz->assignToEntity($circle, $dueDate);
```

---

#### 4.2 Certificate Generation System
**Timeline**: Week 7-8 (5-6 days)

**Tasks**:
- [ ] Create `Certificate` model with polymorphic relationships
- [ ] Design certificate templates (Arabic + English)
- [ ] Build certificate generation service (PDF)
- [ ] Add auto-generation for course completion
- [ ] Create manual certificate issuance for teachers
- [ ] Build certificate verification system (QR codes)
- [ ] Add certificate gallery for students
- [ ] Create certificate download functionality
- [ ] Add certificate sharing (social media)
- [ ] Implement certificate revocation (if needed)

**Deliverables**:
- `app/Models/Certificate.php`
- `database/migrations/2025_11_19_create_certificates_table.php`
- `app/Services/CertificateService.php`
- `resources/views/certificates/templates/quran-completion.blade.php`
- `resources/views/certificates/templates/course-completion.blade.php`
- `resources/views/student/certificates/index.blade.php`
- `app/Filament/Resources/CertificateResource.php`
- `public/certificates/` (storage directory)

**Certificate Triggers**:
```php
// Auto-generation
- Recorded course 100% completion
- Interactive course completion (all sessions attended)

// Manual issuance
- Teacher awards certificate to Quran circle student
- Teacher awards certificate to academic student
- Admin issues custom certificates
```

---

#### 4.3 Trial Session System Completion
**Timeline**: Week 8 (3-4 days)

**Tasks**:
- [ ] Add login requirement to trial request form
- [ ] Build teacher/admin approval interface (Filament)
- [ ] Create trial scheduling workflow
- [ ] Add trial session to teacher calendar
- [ ] Implement student notifications (approval, scheduling)
- [ ] Build student trial session viewing
- [ ] Add trial expiration logic (7 days after approval)
- [ ] Exclude trials from performance calculations
- [ ] Add trial session badge/indicator in UI
- [ ] Create trial analytics for teachers

**Deliverables**:
- Update `app/Models/QuranTrialRequest.php`
- `database/migrations/2025_11_20_add_expiration_to_trial_requests.php`
- `app/Filament/Teacher/Resources/TrialRequestResource.php` (update)
- `resources/views/student/trial-sessions/index.blade.php`
- `app/Services/TrialSessionService.php`
- `app/Notifications/TrialApproved.php`
- `app/Notifications/TrialScheduled.php`

**Trial Expiration Logic**:
```php
// Expire trial requests after 7 days of approval if not scheduled
TrialRequest::where('status', 'approved')
    ->where('approved_at', '<', now()->subDays(7))
    ->whereNull('scheduled_at')
    ->update(['status' => 'expired']);
```

---

#### 4.4 Advanced Analytics Dashboard
**Timeline**: Week 8 (4-5 days)

**Tasks**:
- [ ] Create academy-wide analytics dashboard
- [ ] Add teacher performance metrics
- [ ] Build student engagement analytics
- [ ] Create revenue/payment analytics
- [ ] Add session completion rates
- [ ] Build attendance trends visualization
- [ ] Create course enrollment analytics
- [ ] Add comparative analytics (month-over-month)
- [ ] Implement exportable reports
- [ ] Create real-time metrics dashboard

**Deliverables**:
- `app/Filament/Widgets/AcademyAnalytics.php`
- `app/Filament/Widgets/TeacherPerformance.php`
- `app/Filament/Widgets/StudentEngagement.php`
- `app/Filament/Widgets/RevenueAnalytics.php`
- `app/Services/AnalyticsService.php`
- `resources/views/filament/widgets/analytics-dashboard.blade.php`

---

## Database Schema Changes

### New Tables

#### 1. `meetings` (Phase 1.1)
```sql
CREATE TABLE meetings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    meetable_type VARCHAR(255) NOT NULL,
    meetable_id BIGINT UNSIGNED NOT NULL,
    academy_id BIGINT UNSIGNED NOT NULL,
    livekit_room_name VARCHAR(255) NOT NULL UNIQUE,
    livekit_room_id VARCHAR(255),
    status ENUM('scheduled', 'active', 'ended', 'cancelled') DEFAULT 'scheduled',
    scheduled_start_at TIMESTAMP NOT NULL,
    actual_start_at TIMESTAMP NULL,
    actual_end_at TIMESTAMP NULL,
    recording_enabled BOOLEAN DEFAULT FALSE,
    recording_url VARCHAR(500),
    participant_count INT DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_meetable (meetable_type, meetable_id),
    INDEX idx_academy_status (academy_id, status),
    INDEX idx_scheduled (scheduled_start_at),
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE
);
```

#### 2. `meeting_participants` (Phase 1.1)
```sql
CREATE TABLE meeting_participants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    meeting_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_at TIMESTAMP NULL,
    left_at TIMESTAMP NULL,
    duration_seconds INT DEFAULT 0,
    is_host BOOLEAN DEFAULT FALSE,
    connection_quality ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_meeting_user (meeting_id, user_id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 3. `academic_session_attendances` (Phase 1.2)
```sql
CREATE TABLE academic_session_attendances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    academic_session_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('present', 'absent', 'late', 'partial') DEFAULT 'absent',
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    join_time TIMESTAMP NULL,
    leave_time TIMESTAMP NULL,
    late_minutes INT DEFAULT 0,
    is_manual_override BOOLEAN DEFAULT FALSE,
    override_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_student (academic_session_id, student_id),
    INDEX idx_academy_student (academy_id, student_id),
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 4. `interactive_session_attendances` (Phase 1.2)
```sql
-- Similar structure to academic_session_attendances
-- Replace academic_session_id with interactive_course_session_id
```

#### 5. `academy_settings` (Phase 1.3)
```sql
CREATE TABLE academy_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL UNIQUE,
    timezone VARCHAR(50) DEFAULT 'Asia/Riyadh',
    default_session_duration ENUM('30', '60', '90') DEFAULT '60',
    default_preparation_minutes INT DEFAULT 15,
    default_buffer_minutes INT DEFAULT 5,
    default_late_tolerance_minutes INT DEFAULT 10,
    default_attendance_threshold_percentage DECIMAL(5,2) DEFAULT 80.00,
    trial_session_duration INT DEFAULT 30,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE
);
```

#### 6. `session_reports` (Phase 3.3)
```sql
CREATE TABLE session_reports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    reportable_type VARCHAR(255) NOT NULL,
    reportable_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    report_period ENUM('session', 'monthly', 'overall') DEFAULT 'session',
    period_start_date DATE,
    period_end_date DATE,
    attendance_data JSON NOT NULL,
    homework_data JSON,
    performance_data JSON NOT NULL,
    overall_score DECIMAL(5,2),
    teacher_notes TEXT,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reportable (reportable_type, reportable_id),
    INDEX idx_student_period (student_id, report_period),
    INDEX idx_academy_student (academy_id, student_id),
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 7. `quizzes` (Phase 4.1)
```sql
CREATE TABLE quizzes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    assignable_type VARCHAR(255),
    assignable_id BIGINT UNSIGNED,
    teacher_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INT DEFAULT 30,
    total_points INT DEFAULT 100,
    pass_percentage DECIMAL(5,2) DEFAULT 60.00,
    max_attempts INT DEFAULT 1,
    is_published BOOLEAN DEFAULT FALSE,
    due_date TIMESTAMP NULL,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_assignable (assignable_type, assignable_id),
    INDEX idx_academy_teacher (academy_id, teacher_id),
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 8. `quiz_questions` (Phase 4.1)
```sql
CREATE TABLE quiz_questions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_type ENUM('mcq', 'true_false', 'essay') NOT NULL,
    question_text TEXT NOT NULL,
    points INT DEFAULT 10,
    order_number INT DEFAULT 0,
    options JSON,
    correct_answer JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quiz (quiz_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
```

#### 9. `quiz_attempts` (Phase 4.1)
```sql
CREATE TABLE quiz_attempts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    attempt_number INT DEFAULT 1,
    started_at TIMESTAMP NOT NULL,
    submitted_at TIMESTAMP NULL,
    answers JSON NOT NULL,
    auto_graded_score DECIMAL(5,2),
    manual_graded_score DECIMAL(5,2),
    total_score DECIMAL(5,2),
    is_passed BOOLEAN DEFAULT FALSE,
    graded_by BIGINT UNSIGNED,
    graded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quiz_student (quiz_id, student_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 10. `certificates` (Phase 4.2)
```sql
CREATE TABLE certificates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    academy_id BIGINT UNSIGNED NOT NULL,
    certifiable_type VARCHAR(255) NOT NULL,
    certifiable_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    issued_by BIGINT UNSIGNED NOT NULL,
    certificate_code VARCHAR(50) NOT NULL UNIQUE,
    certificate_type ENUM('completion', 'achievement', 'participation', 'custom') NOT NULL,
    title_ar VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    description_ar TEXT,
    description_en TEXT,
    issue_date DATE NOT NULL,
    verification_url VARCHAR(500),
    pdf_path VARCHAR(500),
    is_revoked BOOLEAN DEFAULT FALSE,
    revoked_at TIMESTAMP NULL,
    revoked_reason TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_certifiable (certifiable_type, certifiable_id),
    INDEX idx_student (student_id),
    INDEX idx_code (certificate_code),
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 11. `teacher_availabilities` (Phase 3.2)
```sql
CREATE TABLE teacher_availabilities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0=Sunday, 6=Saturday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    exception_dates JSON, -- Array of dates when not available
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_day (teacher_id, day_of_week),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Modified Tables

#### 1. Add attendance settings to entities
```sql
-- Add to: quran_circles, interactive_courses
ALTER TABLE quran_circles ADD COLUMN preparation_minutes INT DEFAULT 15;
ALTER TABLE quran_circles ADD COLUMN buffer_minutes INT DEFAULT 5;
ALTER TABLE quran_circles ADD COLUMN late_tolerance_minutes INT DEFAULT 10;
ALTER TABLE quran_circles ADD COLUMN attendance_threshold_percentage DECIMAL(5,2) DEFAULT 80.00;

-- Repeat for interactive_courses
```

#### 2. Add homework to interactive courses
```sql
ALTER TABLE interactive_course_sessions ADD COLUMN homework_description TEXT NULL;
ALTER TABLE interactive_course_sessions ADD COLUMN homework_due_date TIMESTAMP NULL;
```

#### 3. Add payment configuration to interactive courses
```sql
ALTER TABLE interactive_courses ADD COLUMN payment_type ENUM('fixed', 'per_student', 'per_session') DEFAULT 'fixed';
ALTER TABLE interactive_courses ADD COLUMN teacher_fixed_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE interactive_courses ADD COLUMN amount_per_student DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE interactive_courses ADD COLUMN amount_per_session DECIMAL(10,2) DEFAULT 0.00;
```

#### 4. Add expiration to trial requests
```sql
ALTER TABLE quran_trial_requests ADD COLUMN expires_at TIMESTAMP NULL;
ALTER TABLE quran_trial_requests ADD COLUMN approved_at TIMESTAMP NULL;
```

---

## Service Layer Architecture

### Core Services

#### 1. `MeetingService` (Phase 1.1)
```php
namespace App\Services;

use App\Models\Meeting;
use LiveKit\AccessToken;
use LiveKit\RoomServiceClient;

class MeetingService
{
    public function createMeeting(
        string $meetableType,
        int $meetableId,
        Carbon $scheduledStart,
        array $options = []
    ): Meeting;

    public function generateAccessToken(
        Meeting $meeting,
        User $user,
        array $permissions = []
    ): string;

    public function startMeeting(Meeting $meeting): void;

    public function endMeeting(Meeting $meeting): void;

    public function trackParticipant(
        Meeting $meeting,
        User $user,
        string $event // 'join' | 'leave'
    ): void;

    public function getRoomInfo(string $roomName): array;
}
```

#### 2. `UnifiedAttendanceService` (Phase 1.2)
```php
namespace App\Services;

class UnifiedAttendanceService
{
    public function calculateAttendance(
        Meeting $meeting,
        User $user,
        ?array $thresholds = null
    ): array;

    public function autoTrackFromMeeting(Meeting $meeting): void;

    public function manualOverride(
        Attendance $attendance,
        string $status,
        ?string $reason = null
    ): Attendance;

    public function getAttendanceReport(
        $session, // QuranSession|AcademicSession|etc.
        User $user
    ): array;

    public function calculateAttendancePercentage(
        Carbon $joinTime,
        Carbon $leaveTime,
        int $sessionDurationMinutes,
        int $lateToleranceMinutes
    ): float;
}
```

#### 3. `ReportGenerationService` (Phase 3.3)
```php
namespace App\Services;

class ReportGenerationService
{
    public function generateSessionReport(
        $session,
        User $student,
        array $performanceData
    ): SessionReport;

    public function generateMonthlyReport(
        User $student,
        string $sectionType, // 'quran' | 'academic' | 'interactive'
        Carbon $month
    ): SessionReport;

    public function generateOverallReport(
        User $student,
        $entity // Circle | Course | Subscription
    ): SessionReport;

    public function calculateOverallScore(array $reportData): float;

    public function publishReport(SessionReport $report): void;
}
```

#### 4. `HomeworkService` (Phase 2.3)
```php
namespace App\Services;

class HomeworkService
{
    public function createHomework(
        $session,
        array $data
    ): Homework;

    public function submitHomework(
        Homework $homework,
        User $student,
        array $submission
    ): HomeworkSubmission;

    public function gradeHomework(
        HomeworkSubmission $submission,
        float $grade,
        ?string $feedback = null
    ): HomeworkSubmission;

    public function getStudentHomework(
        User $student,
        ?string $status = null
    ): Collection;
}
```

#### 5. `QuizService` (Phase 4.1)
```php
namespace App\Services;

class QuizService
{
    public function createQuiz(array $data): Quiz;

    public function addQuestion(Quiz $quiz, array $questionData): QuizQuestion;

    public function startAttempt(Quiz $quiz, User $student): QuizAttempt;

    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt;

    public function autoGradeAttempt(QuizAttempt $attempt): float;

    public function manualGradeEssay(
        QuizAttempt $attempt,
        int $questionId,
        float $score
    ): void;

    public function getQuizResults(Quiz $quiz): array;
}
```

#### 6. `CertificateService` (Phase 4.2)
```php
namespace App\Services;

class CertificateService
{
    public function generateCertificate(
        User $student,
        $certifiable,
        string $type,
        ?User $issuedBy = null
    ): Certificate;

    public function generatePDF(Certificate $certificate): string;

    public function verifyCertificate(string $code): ?Certificate;

    public function revokeCertificate(
        Certificate $certificate,
        string $reason
    ): void;

    public function checkAutoGeneration($entity): void;
}
```

---

## UI/UX Implementation

### Component Library Structure

```
resources/views/components/
├── meetings/
│   ├── livekit-room.blade.php           # Main LiveKit room component
│   ├── participant-list.blade.php       # Participant sidebar
│   ├── controls.blade.php               # Meeting controls (mute, camera, etc.)
│   └── chat.blade.php                   # In-meeting chat
├── attendance/
│   ├── attendance-tracker.blade.php     # Real-time attendance display
│   ├── manual-override-modal.blade.php  # Teacher manual override
│   └── attendance-badge.blade.php       # Status badge (present/late/absent)
├── homework/
│   ├── submission-form.blade.php        # Student submission UI
│   ├── grading-interface.blade.php      # Teacher grading UI
│   └── homework-card.blade.php          # Homework display card
├── reports/
│   ├── report-card.blade.php            # Unified report card
│   ├── quran-performance.blade.php      # Quran-specific metrics
│   ├── academic-performance.blade.php   # Academic-specific metrics
│   └── progress-chart.blade.php         # Visual progress charts
├── quizzes/
│   ├── quiz-taker.blade.php             # Student quiz interface
│   ├── question-display.blade.php       # Individual question component
│   └── results-display.blade.php        # Quiz results view
└── certificates/
    ├── certificate-template.blade.php   # PDF template
    ├── certificate-card.blade.php       # Gallery card
    └── verification-badge.blade.php     # QR code verification
```

### Livewire Components

```php
// Real-time attendance tracking
Livewire::component('attendance-tracker', AttendanceTracker::class);

// Live meeting interface
Livewire::component('livekit-meeting', LivekitMeeting::class);

// Homework submission
Livewire::component('homework-submission', HomeworkSubmission::class);

// Quiz taking
Livewire::component('quiz-taker', QuizTaker::class);

// Report generation
Livewire::component('report-generator', ReportGenerator::class);
```

### Key UI Pages to Build

#### Student Pages
```
/student/dashboard                     # Central hub (NEW)
/student/meetings/{meeting}/join       # LiveKit meeting room (NEW)
/student/homework                      # Homework listing (NEW)
/student/homework/{id}/submit          # Submit homework (NEW)
/student/quizzes                       # Quiz listing (NEW)
/student/quizzes/{id}/take             # Take quiz (NEW)
/student/reports                       # All reports (UPDATE)
/student/certificates                  # Certificate gallery (NEW)
```

#### Teacher Pages
```
/teacher/dashboard                     # Central hub (UPDATE)
/teacher/availability                  # Manage availability (NEW)
/teacher/homework/grade                # Grade homework (NEW)
/teacher/quizzes/create                # Create quiz (Filament better)
/teacher/reports/generate              # Generate reports (NEW)
/teacher/certificates/issue            # Issue certificates (Filament)
```

#### Parent Pages
```
/parent/dashboard                      # Parent home (NEW)
/parent/children                       # Children list (NEW)
/parent/children/{id}/sessions         # Child sessions (NEW)
/parent/children/{id}/progress         # Child progress (NEW)
/parent/children/{id}/reports          # Child reports (NEW)
/parent/payments                       # Payment management (UPDATE)
```

#### Public Pages
```
/interactive-courses                   # Browse courses (UPDATE)
/interactive-courses/{id}              # Course detail (UPDATE)
/interactive-courses/{id}/enroll       # Enrollment flow (NEW)
/academic-teachers                     # Browse teachers (EXISTS)
/academic-teachers/{id}/subscribe      # Subscribe flow (NEW)
```

---

## Testing Strategy

### Unit Tests
```
tests/Unit/
├── Services/
│   ├── MeetingServiceTest.php
│   ├── UnifiedAttendanceServiceTest.php
│   ├── ReportGenerationServiceTest.php
│   ├── HomeworkServiceTest.php
│   ├── QuizServiceTest.php
│   └── CertificateServiceTest.php
├── Models/
│   ├── MeetingTest.php
│   ├── SessionReportTest.php
│   ├── QuizTest.php
│   └── CertificateTest.php
└── Enums/
    └── SessionDurationTest.php
```

### Feature Tests
```
tests/Feature/
├── MeetingManagementTest.php
├── AttendanceTrackingTest.php
├── HomeworkSubmissionTest.php
├── QuizTakingTest.php
├── ReportGenerationTest.php
├── CertificateIssuanceTest.php
├── InteractiveCourseEnrollmentTest.php
└── AcademicSubscriptionTest.php
```

### Browser Tests (Dusk)
```
tests/Browser/
├── StudentJoinsMeetingTest.php
├── TeacherGradesHomeworkTest.php
├── StudentTakesQuizTest.php
├── ParentViewsReportsTest.php
└── EnrollmentFlowTest.php
```

---

## Deployment Plan

### Phase 1 Deployment (Week 3)
```bash
# 1. Run migrations
php artisan migrate

# 2. Seed academy settings
php artisan db:seed --class=AcademySettingsSeeder

# 3. Update existing sessions with meetings
php artisan app:migrate-meetings

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Install LiveKit dependencies
npm install livekit-client @livekit/components-react
npm run build

# 6. Configure LiveKit in .env
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_api_secret
LIVEKIT_URL=wss://your-livekit-server.com
```

### Phase 2 Deployment (Week 5)
```bash
# 1. Run new migrations
php artisan migrate

# 2. Update payment configurations
php artisan app:update-payment-configs

# 3. Seed interactive course settings
php artisan db:seed --class=InteractiveCourseSettingsSeeder

# 4. Clear caches
php artisan optimize:clear
```

### Phase 3 Deployment (Week 7)
```bash
# 1. Run migrations
php artisan migrate

# 2. Generate historical reports
php artisan app:generate-historical-reports

# 3. Setup parent accounts
php artisan app:setup-parent-accounts

# 4. Clear caches
php artisan optimize:clear
```

### Phase 4 Deployment (Week 8)
```bash
# 1. Run final migrations
php artisan migrate

# 2. Setup certificate templates
php artisan app:setup-certificate-templates

# 3. Final cache clear
php artisan optimize:clear

# 4. Run full test suite
php artisan test
```

---

## Risk Assessment & Mitigation

### High-Risk Areas

#### 1. LiveKit Integration
**Risk**: Complex real-time functionality may have integration issues
**Mitigation**:
- Thorough testing in sandbox environment
- Fallback to Google Meet temporarily if issues arise
- Gradual rollout (Quran sessions first, then expand)

#### 2. Data Migration
**Risk**: Breaking existing features during attendance unification
**Mitigation**:
- Comprehensive backups before each deployment
- Feature flags for gradual rollout
- Rollback scripts prepared for each migration

#### 3. Performance Impact
**Risk**: Real-time attendance tracking may impact server performance
**Mitigation**:
- Queue-based processing for attendance calculations
- Redis caching for meeting data
- Load testing before production deployment

#### 4. Payment Processing
**Risk**: Interactive course payment calculation errors
**Mitigation**:
- Extensive unit tests for payment calculations
- Admin review before teacher payouts
- Transaction logging for audit trail

---

## Success Metrics

### Phase 1 Success Criteria
- [ ] 100% of sessions using unified Meeting model
- [ ] LiveKit rooms functional for all session types
- [ ] Real-time attendance tracking <500ms latency
- [ ] Zero breaking changes to existing features

### Phase 2 Success Criteria
- [ ] Interactive course enrollment conversion rate >70%
- [ ] Academic subscription booking flow <3 minutes
- [ ] Homework submission rate >80%
- [ ] Teacher grading time <5 minutes per submission

### Phase 3 Success Criteria
- [ ] Parent dashboard adoption >60% of parents
- [ ] Report generation time <2 seconds
- [ ] Teacher availability calendar accuracy >95%

### Phase 4 Success Criteria
- [ ] Quiz completion rate >75%
- [ ] Certificate generation time <30 seconds
- [ ] Analytics dashboard load time <1 second

---

## Appendix

### A. Configuration Examples

#### LiveKit Configuration
```env
LIVEKIT_API_KEY=APIxxxxxxxxxxxx
LIVEKIT_API_SECRET=secretxxxxxxxxxx
LIVEKIT_URL=wss://itqan.livekit.cloud
LIVEKIT_REGION=global
```

#### Academy Settings JSON Structure
```json
{
  "timezone": "Asia/Riyadh",
  "default_session_duration": 60,
  "attendance": {
    "preparation_minutes": 15,
    "buffer_minutes": 5,
    "late_tolerance_minutes": 10,
    "threshold_percentage": 80
  },
  "trial": {
    "duration_minutes": 30,
    "expiration_days": 7
  },
  "notifications": {
    "session_reminder_minutes": 15,
    "homework_reminder_days": 1
  }
}
```

### B. API Endpoints

#### Meeting Endpoints
```
POST   /api/meetings/{meeting}/join
POST   /api/meetings/{meeting}/leave
GET    /api/meetings/{meeting}/participants
POST   /api/meetings/{meeting}/end
```

#### Attendance Endpoints
```
GET    /api/sessions/{session}/attendance
POST   /api/attendance/{id}/override
GET    /api/students/{student}/attendance-report
```

#### Homework Endpoints
```
GET    /api/homework
GET    /api/homework/{id}
POST   /api/homework/{id}/submit
POST   /api/homework/{id}/grade
```

### C. Filament Resources Priority

**Phase 1**:
- AcademySettingsResource
- MeetingResource (read-only, for debugging)

**Phase 2**:
- InteractiveCourseResource (update)
- AcademicSubscriptionResource (update)
- HomeworkResource

**Phase 3**:
- SessionReportResource
- TeacherAvailabilityResource

**Phase 4**:
- QuizResource
- CertificateResource
- AnalyticsDashboard

---

## Timeline Summary

| Phase | Duration | Key Deliverables | Dependencies |
|-------|----------|------------------|--------------|
| **Phase 1** | Weeks 1-3 | Meeting system, Attendance, Config | None |
| **Phase 2** | Weeks 3-5 | Interactive courses, Academic booking, Homework | Phase 1 |
| **Phase 3** | Weeks 5-7 | Parent dashboard, Availability, Reports | Phase 2 |
| **Phase 4** | Weeks 7-8 | Quizzes, Certificates, Trials, Analytics | Phase 3 |

**Total Estimated Timeline**: 8-10 weeks

---

## Next Steps

1. **Review & Approval**: Stakeholder review of this plan
2. **Environment Setup**: Configure LiveKit sandbox environment
3. **Sprint Planning**: Break Phase 1 into 2-week sprints
4. **Begin Development**: Start with Meeting model implementation
5. **Daily Standups**: Track progress and address blockers

---

**Document Version**: 1.0
**Created**: 2025-11-10
**Author**: Claude Code AI Assistant
**Status**: Pending Approval
