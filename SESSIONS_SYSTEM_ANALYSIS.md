# ITQAN Platform - Sessions System Implementation Analysis

## 1. ARCHITECTURE OVERVIEW

The sessions system is built using a **polymorphic inheritance pattern** with a base class (`BaseSession`) that provides common functionality for all session types. This eliminates code duplication (~800 lines) while maintaining consistency across different session types.

### Session Types
- **QuranSession** - Islamic Quran teaching sessions (individual & group circles)
- **AcademicSession** - Academic subject lessons (individual & course-based)
- **InteractiveCourseSession** - Interactive online courses

All inherit from `BaseSession` which implements the `MeetingCapable` interface for live meeting functionality.

---

## 2. SESSION MODELS & RELATIONSHIPS

### 2.1 BaseSession (Abstract Base Class)
**Location:** `/app/Models/BaseSession.php`

**Purpose:** Provides common functionality shared across all session types.

**Key Features:**
- Implements `MeetingCapable` interface for LiveKit meeting integration
- Uses `HasMeetings` trait for meeting management
- Soft deletes enabled (`SoftDeletes` trait)

**Common Relationships:**
```
BaseSession
├── academy() → Academy
├── meetingAttendances() → MeetingAttendance (polymorphic)
├── cancelledBy() → User
├── createdBy() → User
├── updatedBy() → User
└── scheduledBy() → User
```

**Common Scopes:**
- `scheduled()` - Sessions with SCHEDULED status
- `completed()` - Completed sessions
- `cancelled()` - Cancelled sessions
- `ongoing()` - Currently happening sessions
- `today()` - Sessions scheduled for today
- `upcoming()` - Future scheduled sessions
- `past()` - Past sessions

**Key Methods:**
- `generateMeetingLink()` - Creates LiveKit meeting room
- `isMeetingValid()` - Checks if meeting is still valid
- `getMeetingJoinUrl()` - Returns join URL
- `endMeeting()` - Closes the meeting room
- `getStatusDisplayData()` - Returns formatted status with UI data

---

### 2.2 QuranSession
**Location:** `/app/Models/QuranSession.php`

**Session Types:**
- `individual` - One-on-one sessions with student
- `circle` (or `group`) - Group sessions for multiple students
- `trial` - Trial sessions for new students
- `assessment` - Assessment sessions

**Specific Relationships:**
```
QuranSession
├── quranTeacher() → User (teacher profile)
├── subscription() → QuranSubscription
├── circle() → QuranCircle (group sessions)
├── individualCircle() → QuranIndividualCircle (individual sessions)
├── student() → User (for individual sessions)
├── trialRequest() → QuranTrialRequest
├── makeupFor() → QuranSession (parent makeup session)
├── makeupSessions() → QuranSession (child makeup sessions)
├── homework() → QuranHomework (legacy)
├── sessionHomework() → QuranSessionHomework (new system)
├── homeworkAssignments() → QuranHomeworkAssignment
├── attendances() → QuranSessionAttendance
└── studentReports() → StudentSessionReport
```

**Quran-Specific Fields:**
- **Progress Tracking:**
  - `current_surah`, `current_verse`, `current_page`, `current_face`
  - `verses_covered_start/end`, `page_covered_start/end`
  - `verses_memorized_today`, `papers_memorized_today`
  - `papers_covered_today`

- **Performance Metrics:**
  - `recitation_quality` (0-10)
  - `tajweed_accuracy` (0-10)
  - `mistakes_count`
  - `common_mistakes` (array)
  - `areas_for_improvement` (array)

- **Session Management:**
  - `session_type` - individual/circle
  - `location_type` - online/physical/hybrid
  - `is_makeup_session` - true if makeup session
  - `makeup_session_for` - ID of original session
  - `recording_enabled`, `recording_url`
  - `subscription_counted` - tracks if session was counted towards subscription

- **Homework & Learning:**
  - `homework_assigned`, `homework_details`
  - `materials_used` (array)
  - `lesson_objectives` (array)
  - `learning_outcomes` (array)
  - `assessment_results` (array)

- **Follow-up:**
  - `follow_up_required` (boolean)
  - `follow_up_notes`
  - `next_session_plan`

**Key Methods:**
- `markAsOngoing()` - Transition to ONGOING status
- `markAsCompleted(additionalData)` - Mark session complete with data
- `markAsCancelled(reason, cancelledBy)` - Cancel session
- `markAsAbsent(reason)` - Mark as absent (individual only)
- `countsTowardsSubscription()` - Check if counts towards subscription
- `updateSubscriptionUsage()` - Update subscription session count
- `createMakeupSession()` - Create makeup session
- `recordProgress()` - Record Quran progress
- `assignHomework()` - Assign homework
- `startRecording()`, `stopRecording()` - Recording management
- `addFeedback(type, feedback)` - Add teacher/student/parent feedback
- `rate(rating)` - Rate session (1-5)
- `updateProgressByPapers()` - Update using paper-based tracking

---

### 2.3 AcademicSession
**Location:** `/app/Models/AcademicSession.php`

**Session Types:**
- `individual` - One-on-one academic lessons
- `interactive_course` - Course-based sessions with multiple students

**Specific Relationships:**
```
AcademicSession
├── academicTeacher() → AcademicTeacherProfile
├── subscription() → AcademicSubscription
├── academicIndividualLesson() → AcademicIndividualLesson
├── interactiveCourseSession() → InteractiveCourseSession
├── student() → User
├── sessionReports() → AcademicSessionReport
├── attendanceMarkedBy() → User
└── makeupSessionFor() → AcademicSession
```

**Academic-Specific Fields:**
- **Content:**
  - `session_topics_covered` (text)
  - `lesson_content` (text)
  - `learning_outcomes` (array)

- **Assessment:**
  - `session_grade` (decimal 0.0-10.0)
  - `session_notes`, `teacher_feedback`, `student_feedback`, `parent_feedback`
  - `overall_rating` (1-5 stars)

- **Homework:**
  - `homework_description` (text)
  - `homework_file` (file path)

- **Scheduling:**
  - `is_template` - true if session is a template
  - `is_generated` - true if auto-generated
  - `is_scheduled` - true if teacher has scheduled
  - `teacher_scheduled_at` - when teacher scheduled

- **Google Calendar Integration:**
  - `google_event_id`, `google_calendar_id`
  - `google_meet_url`, `google_meet_id`
  - `google_attendees` (array)

---

### 2.4 InteractiveCourseSession
**Location:** `/app/Models/InteractiveCourseSession.php`

**Specific Relationships:**
```
InteractiveCourseSession
├── course() → InteractiveCourse
├── attendances() → InteractiveSessionAttendance
├── homework() → InteractiveCourseHomework
├── presentStudents() → InteractiveSessionAttendance (present only)
├── absentStudents() → InteractiveSessionAttendance (absent only)
└── lateStudents() → InteractiveSessionAttendance (late only)
```

**Specific Fields:**
- `course_id` - Foreign key to course
- `session_number` - Session number in sequence
- `scheduled_date`, `scheduled_time` - Separate date/time fields
- `attendance_count` - Number of attendees
- `materials_uploaded` (boolean)
- `homework_assigned` (boolean)
- `homework_description`, `homework_due_date`, `homework_max_score`
- `allow_late_submissions` (boolean)

**Key Methods:**
- `canStart()` - Check if can start
- `canCancel()` - Check if can cancel
- `start()`, `complete()`, `cancel()` - State transitions
- `updateAttendanceCount()` - Update attendance count
- `getAttendanceRate()` - Calculate attendance percentage
- `getAverageParticipationScore()` - Average participation
- `generateGoogleMeetLink()` - Generate Google Meet link

---

## 3. SESSION STATUS TRANSITIONS

### Status Enum
**Location:** `/app/Enums/SessionStatus.php`

**Status Values:**
```
UNSCHEDULED  → Created but not scheduled by teacher
SCHEDULED    → Teacher has set date/time
READY        → Meeting created, preparation time begun
ONGOING      → Session is currently happening
COMPLETED    → Session finished successfully
CANCELLED    → Cancelled by teacher/admin
ABSENT       → Student didn't attend (individual sessions only)
```

**Status Transitions:**
```
UNSCHEDULED → SCHEDULED (teacher schedules)
SCHEDULED   → READY (auto-transition ~15 min before)
READY       → ONGOING (first participant joins)
ONGOING     → COMPLETED (session ends)
READY       → ABSENT (no-show after grace period)
SCHEDULED   → CANCELLED (teacher cancels)
READY       → CANCELLED (teacher cancels)
```

**Status Rules:**
- `canStart()` → SCHEDULED or READY can start
- `canComplete()` → SCHEDULED, READY, or ONGOING can complete
- `canCancel()` → SCHEDULED or READY can cancel
- `canReschedule()` → SCHEDULED or READY can reschedule
- `countsTowardsSubscription()` → Only COMPLETED and ABSENT count

**Status Colors/Icons:**
- UNSCHEDULED → Gray (ri-draft-line)
- SCHEDULED → Blue (ri-calendar-line)
- READY → Green (ri-video-line)
- ONGOING → Green (ri-live-line)
- COMPLETED → Green (ri-check-circle-line)
- CANCELLED → Red (ri-close-circle-line)
- ABSENT → Red (ri-user-x-line)

---

## 4. SESSION LIFECYCLE & WORKFLOW

### 4.1 Creation Phase
```
Teacher/Admin Creates Session
├── Set: academy_id, title, duration_minutes, session_type
├── Generate: session_code (unique per academy)
├── Status: UNSCHEDULED
└── Optional: lesson_objectives, description, location_type
```

### 4.2 Scheduling Phase
```
Teacher Schedules Session
├── Set: scheduled_at timestamp
├── Check: teacher has no conflicts
├── Update: status → SCHEDULED
├── Record: teacher_scheduled_at, scheduled_by
└── Optional: custom title, lesson objectives
```

### 4.3 Preparation Phase (Auto - 15 minutes before)
```
System Auto-Transitions: SCHEDULED → READY
├── Check: scheduled_at - 15 minutes <= now
├── Create: LiveKit meeting room
├── Generate: meeting_link, meeting_room_name, meeting_data
├── Store: meeting_expires_at
└── Log: preparation_completed_at
```

### 4.4 Active Session Phase
```
First Participant Joins: READY → ONGOING
├── Validate: can join based on timing
├── Record: started_at = now()
├── Update: status = ONGOING
└── Track: meeting participants in real-time
```

### 4.5 Session Completion Phase (Auto after duration + buffer)
```
Auto-Transition: ONGOING → COMPLETED or ABSENT
├── Check: now >= scheduled_at + duration + buffer
├── If students attended:
│   ├── Status: COMPLETED
│   ├── Record: ended_at, actual_duration_minutes
│   ├── Close: meeting room
│   └── Update: subscription usage (for individual)
├── Else (no attendance):
│   ├── Status: ABSENT (individual only)
│   ├── Record: attendance_status = absent
│   ├── Update: subscription usage
│   └── Handle: absence follow-up
└── Create: StudentSessionReport with auto-calculated metrics
```

### 4.6 Post-Session Phase
```
After Session Completion
├── Record: attendance data from meeting
├── Create: StudentSessionReport (comprehensive teacher-verified data)
├── Optional: teacher fills additional feedback
├── Optional: student rates session
├── Optional: create homework assignments
└── Optional: create follow-up tasks
```

---

## 5. ATTENDANCE TRACKING SYSTEM

### 5.1 Models
**QuranSessionAttendance** (`/app/Models/QuranSessionAttendance.php`)
- Extends `BaseSessionAttendance`
- Quran-specific metrics:
  - `recitation_quality` (0-10)
  - `tajweed_accuracy` (0-10)
  - `papers_memorized_today`, `verses_memorized_today`
  - `pages_memorized_today`, `pages_reviewed_today`
  - `verses_reviewed`, `homework_completion`

**AcademicSessionAttendance** (`/app/Models/AcademicSessionAttendance.php`)
- Similar structure for academic sessions
- Academic-specific metrics

**InteractiveSessionAttendance** (`/app/Models/InteractiveSessionAttendance.php`)
- Course-specific metrics:
  - `video_completion_percentage`
  - `quiz_completion`
  - `exercises_completed`
  - `interaction_score`

**StudentSessionReport** (`/app/Models/StudentSessionReport.php`)
- **Primary source of truth for attendance** (takes precedence over MeetingAttendance)
- Comprehensive teacher-verified data
- Fields:
  - `attendance_status` (present/absent/late/left_early/partial)
  - `actual_attendance_minutes` (calculated from join/leave times)
  - `participation_score` (0-10)
  - `meeting_join_time`, `meeting_leave_time`
  - `auto_tracked` (true if auto-calculated by system)
  - `is_auto_calculated` (true if system generated initially)
  - `evaluated_at` (when report was created/updated)
  - Teacher feedback fields

### 5.2 Attendance Tracking Workflow
```
1. Session Starts
   ├── LiveKit tracks participant join/leave
   ├── Record: MeetingAttendance (real-time)
   └── Status: auto_tracked = true

2. Session Ends
   ├── Calculate: actual_duration_minutes from join/leave
   ├── Determine: attendance_status (present/absent/late/etc)
   └── Record: MeetingAttendance with calculated metrics

3. Teacher Review
   ├── Teacher views StudentSessionReport
   ├── Optional: Override attendance if needed
   └── Update: StudentSessionReport with manual corrections

4. Subscription Counting
   ├── If COMPLETED or ABSENT status
   ├── Check: StudentSessionReport attendance_status
   ├── If present/late/partial → count towards subscription
   ├── If absent → count towards subscription (by design)
   └── Update: subscription session usage
```

---

## 6. MEETING MANAGEMENT (LiveKit Integration)

### 6.1 Meeting Capabilities
All session types implement the `MeetingCapable` interface.

**Meeting Creation:**
- Automatic when session transitions to READY (15 min before)
- Via `generateMeetingLink()` method
- Creates LiveKit room with:
  - `meeting_room_name` (unique identifier)
  - `meeting_link` (join URL)
  - `meeting_id` (LiveKit ID)
  - `meeting_platform` (e.g., 'livekit')
  - `meeting_data` (full response from API)
  - `meeting_expires_at` (expiration time)

**Meeting Configuration:**
```
BaseSession Configuration:
├── recording_enabled (varies by type)
├── max_participants (varies by type)
├── duration_minutes (scheduled duration)
└── session_type (quran/academic/interactive)

QuranSession:
├── Individual: max 2, recording enabled
└── Circle: max 50, recording enabled

AcademicSession:
├── Individual: max 2, recording disabled
└── Interactive: max 25, recording disabled

InteractiveCourseSession:
├── max 30, recording enabled
└── breakout_rooms enabled
```

**Meeting Lifecycle:**
```
SCHEDULED         →  READY           →   ONGOING        →   COMPLETED
Generate 15 min        Create room        Track              Close room
before start          & get link         participants        & save stats
```

**Meeting Statistics:**
- `getRoomInfo()` - Get current room status from LiveKit
- `getMeetingStats()` - Get participant count, duration, etc.
- `isUserInMeeting(user)` - Check if user currently in room
- `generateParticipantToken(user)` - Generate access token

---

## 7. DATABASE STRUCTURE

### 7.1 Main Sessions Tables

**quran_sessions**
- Core fields: id, academy_id, session_code, status, title, description
- Timing: scheduled_at, started_at, ended_at, duration_minutes, actual_duration_minutes
- Meeting: meeting_link, meeting_id, meeting_room_name, meeting_data, meeting_expires_at
- Attendance: attendance_status, participants_count, attendance_notes
- Feedback: teacher_feedback, student_feedback, parent_feedback, overall_rating
- Quran fields: current_surah, current_verse, current_page, papers_memorized_today, etc.
- Relations: quran_teacher_id, quran_subscription_id, circle_id, individual_circle_id, student_id
- Management: cancellation_reason, cancelled_by, cancelled_at, subscription_counted
- Audit: created_by, updated_by, scheduled_by, created_at, updated_at, deleted_at

**Indexes:**
```
CREATE INDEX idx_academy_status ON quran_sessions(academy_id, status);
CREATE INDEX idx_teacher_scheduled ON quran_sessions(quran_teacher_id, scheduled_at);
CREATE INDEX idx_student_scheduled ON quran_sessions(student_id, scheduled_at);
CREATE INDEX idx_session_code ON quran_sessions(session_code);
CREATE INDEX idx_session_month_number ON quran_sessions(session_month, monthly_session_number);
```

**academic_sessions**
- Similar structure to quran_sessions
- Academic-specific: session_topics_covered, lesson_content, session_grade, homework_description
- Google integration: google_event_id, google_meet_url, etc.
- Relations: academic_teacher_id, academic_subscription_id, student_id

**interactive_course_sessions**
- Uses scheduled_date + scheduled_time (not scheduled_at)
- Course relation: course_id
- Course-specific: session_number, attendance_count, homework_max_score
- Features: materials_uploaded, homework_assigned, allow_late_submissions

### 7.2 Attendance Tables

**quran_session_attendances**
- Fields: session_id, student_id, attendance_status
- Timing: join_time, leave_time, auto_join_time, auto_leave_time
- Tracking: auto_duration_minutes, auto_tracked, manually_overridden
- Metrics: participation_score, connection_quality_score
- Quran: recitation_quality, tajweed_accuracy, papers_memorized_today, etc.

**academic_session_attendances**
- Similar structure for academic sessions

**interactive_session_attendances**
- Interactive-specific: video_completion_percentage, quiz_completion, exercises_completed

**student_session_reports**
- Primary source for attendance truth
- Fields: session_id, student_id, teacher_id, academy_id
- Status: attendance_status, actual_attendance_minutes
- Tracking: auto_tracked, is_auto_calculated, manually_overridden
- Metrics: participation_score, learning_outcome_achievement
- Audit: evaluated_at, overridden_by, overridden_at

### 7.3 Scheduling Tables

**session_schedules**
- Supports: individual & group scheduling
- Fields: academy_id, quran_teacher_id, schedule_code
- Recurrence: recurrence_pattern (weekly/bi-weekly/monthly/custom)
- Configuration: schedule_data, session_templates
- Duration: start_date, end_date, max_sessions
- Status: active/paused/completed/cancelled
- Tracking: sessions_generated, sessions_completed, sessions_cancelled

**quran_circle_schedules** (for group circle scheduling)
- circle_id, quran_teacher_id
- weekly_schedule (array of day/time pairs)
- schedule_starts_at, schedule_ends_at
- generate_ahead_days, generate_before_hours
- monthly_sessions_count (limit sessions per month)
- Default settings for duration, recording, objectives

---

## 8. SESSION SCHEDULING & GENERATION

### 8.1 Individual Session Scheduling
**Service:** `QuranSessionSchedulingService::scheduleIndividualSession()`

```php
scheduleIndividualSession(
    QuranSession $templateSession,
    Carbon $scheduledAt,
    ?array $additionalData = null
): QuranSession
```

**Process:**
1. Validate template session (must be template, not already scheduled)
2. Validate scheduled_at is in future
3. Check for teacher conflicts
4. Update session: scheduled_at, status, is_scheduled
5. Update individual circle counts
6. Return fresh session instance

**Validation Rules:**
- Cannot schedule in past
- Cannot have teacher conflicts (overlapping sessions)
- Template must not already be scheduled

**Bulk Scheduling:**
`bulkScheduleIndividualSessions(circle, sessionsData)` - Schedule multiple at once

### 8.2 Group Circle Scheduling
**Service:** `QuranSessionSchedulingService::createGroupCircleSchedule()`

```php
createGroupCircleSchedule(
    QuranCircle $circle,
    array $weeklySchedule,
    Carbon $startsAt,
    ?Carbon $endsAt = null,
    array $options = []
): QuranCircleSchedule
```

**Weekly Schedule Format:**
```php
[
    ['day' => 'sunday', 'time' => '09:00'],
    ['day' => 'monday', 'time' => '14:30'],
    ['day' => 'wednesday', 'time' => '10:00'],
]
```

**Available Options:**
- `timezone` - Default: 'Asia/Riyadh'
- `duration` - Duration in minutes (default: 60)
- `generate_ahead_days` - Look-ahead for generation (default: 30)
- `title_template`, `description_template` - Templates for auto-generated sessions
- `recording_enabled` - Enable recording

**Process:**
1. Validate circle doesn't already have active schedule
2. Validate weekly schedule format
3. Create QuranCircleSchedule record
4. Activate schedule (triggers auto-generation)

### 8.3 Auto-Generation
**Automatic Session Generation** happens when:
- Schedule activated
- New month begins
- Look-ahead period expires

**Generation Process:**
- Check schedule weekly patterns
- Generate sessions for all weeks in period
- Respects monthly_sessions_count limit
- Creates QuranSession records with status SCHEDULED

---

## 9. SESSION STATUS MANAGEMENT SERVICE

**Location:** `/app/Services/SessionStatusService.php`

### 9.1 Status Transitions

**SCHEDULED → READY** (Auto, ~15 min before)
```php
transitionToReady(QuranSession $session): bool
```
- Validate status is SCHEDULED
- Create meeting room via generateMeetingLink()
- Update status to READY
- Log transition

**READY → ONGOING** (When first participant joins)
```php
transitionToOngoing(QuranSession $session): bool
```
- Validate status is READY
- Check timing: 15-min early grace period
- Safety: Don't allow if > 2 hours in future
- Update status to ONGOING
- Record started_at timestamp

**ONGOING → COMPLETED** (Auto after duration + buffer)
```php
transitionToCompleted(QuranSession $session): bool
```
- Validate status is ONGOING or READY
- Calculate actual_duration_minutes
- **Close meeting room** (CRITICAL: prevents late joins)
- Update subscription usage for individual sessions
- Create/Update StudentSessionReport
- Log completion

**→ ABSENT** (No-show after grace period)
```php
transitionToAbsent(QuranSession $session): bool
```
- Individual sessions only
- Check: no participants joined within grace period (default: 15 min)
- Mark status ABSENT
- Count towards subscription (by design)
- Record attendance as absent

**SCHEDULED/READY → CANCELLED**
```php
transitionToCancelled(
    QuranSession $session, 
    ?string $reason = null, 
    ?int $cancelledBy = null
): bool
```
- Validate current status allows cancellation
- Update cancellation fields
- **Does NOT count towards subscription**
- Log cancellation

### 9.2 Auto-Transition Detection

**shouldTransitionToReady()** - Check if ready for transition
- Status must be SCHEDULED
- Check: now >= scheduled_at - preparation_minutes
- Safety: Don't process sessions >24h in future or >24h in past

**shouldTransitionToAbsent()** - Check for no-show
- Individual sessions only, status must be READY
- Check: now >= scheduled_at + grace_period
- Check: no meeting attendance recorded
- Return true if should transition

**shouldAutoComplete()** - Check for auto-completion
- Status must be ONGOING or READY
- Check: now >= scheduled_at + duration + buffer

**processStatusTransitions(Collection $sessions): array**
- Process multiple sessions
- Returns: count of transitions by type + errors

---

## 10. HOMEWORK & ASSIGNMENTS

### 10.1 Legacy System
**QuranHomework** - Old homework system
- Direct assignment to sessions
- Fields: pages, verses, type, assigned_at, completed_at

### 10.2 New System
**QuranSessionHomework** - Per-session homework
```
QuranSession → QuranSessionHomework (1:1)
├── total_pages
├── new_memorization_pages
├── review_pages
├── is_active
└── completed_at
```

**QuranHomeworkAssignment** - Per-student assignment
```
QuranSessionHomework → QuranHomeworkAssignment (1:many)
├── student_id
├── completion_status (not_started/in_progress/partially_completed/completed)
├── completion_percentage (0-100)
├── overall_score
└── assignment_date, due_date, completed_at
```

**Method:** `createHomeworkAssignmentsForStudents()`
- Auto-creates assignments for all session students
- Uses getStudentsForSession() to get proper student list

### 10.3 Homework Submissions (Polymorphic)
**HomeworkSubmission** - Unified submission system
```
HomeworkSubmission (polymorphic)
├── submitable_type (QuranSession/AcademicSession/InteractiveCourseSession)
├── submitable_id (specific session ID)
├── student_id
├── submission_date
├── file_path
└── grade, feedback
```

---

## 11. SESSION REPORTING

### 11.1 Student Session Report
**StudentSessionReport** - Comprehensive per-session, per-student record

**Creation:**
- Auto-created when session is created (status = absent initially)
- Updated when session is completed
- Teacher can manually override/correct

**Fields:**
- attendance_status (present/absent/late/left_early/partial)
- actual_attendance_minutes (calculated from join/leave)
- participation_score (0-10)
- meeting_join_time, meeting_leave_time
- auto_tracked (system auto-calculated)
- is_auto_calculated (initially calculated by system)
- manually_overridden (teacher corrected)
- overridden_by, overridden_at
- evaluated_at (when report was created/finalized)
- session_objectives_met (boolean)
- learning_outcome_achievement (percentage)
- teacher_comments, student_feedback

**Critical Decision Rule:**
*StudentSessionReport takes precedence over MeetingAttendance for determining attendance status*

When completing session:
1. Check StudentSessionReport first (teacher-verified data)
2. Fallback to MeetingAttendance only if no StudentSessionReport exists
3. Update subscription based on StudentSessionReport attendance_status

### 11.2 Academic Session Report
**AcademicSessionReport** - Similar structure for academic sessions
- Topics covered
- Learning outcomes achieved
- Grade awarded
- Student performance metrics

### 11.3 Interactive Session Report
**InteractiveSessionReport** - For interactive courses
- Video watch percentage
- Quiz completion rate
- Exercise completion
- Engagement score

---

## 12. KEY DESIGN PATTERNS

### 12.1 Template Method Pattern
`BaseSession` defines the contract with abstract methods:
- `getMeetingType()` - Returns session type identifier
- `getParticipants()` - Returns participant list
- `getMeetingConfiguration()` - Returns meeting config
- `canUserManageMeeting(user)` - Permission check
- `isUserParticipant(user)` - Participant check
- `getMeetingParticipants()` - Returns Collection of participants

Each child class implements these methods with type-specific logic.

### 12.2 Polymorphic Relationships
Meeting attendance is polymorphic:
```
MeetingAttendance
├── session_id + session_type (polymorphic)
├── Can reference: QuranSession, AcademicSession, InteractiveCourseSession
└── Tracks real-time join/leave from LiveKit
```

Homework submissions are polymorphic:
```
HomeworkSubmission
├── submitable_type + submitable_id (polymorphic)
├── Can reference: QuranSession, AcademicSession, InteractiveCourseSession
└── Unified submission tracking
```

### 12.3 Status Enum Pattern
`SessionStatus` enum with built-in business logic:
```php
$status->canStart()              // Validation logic
$status->canComplete()
$status->canCancel()
$status->countsTowardsSubscription()
$status->label()                 // Display text
$status->icon()                  // UI icon
$status->color()                 // UI color
```

### 12.4 Service Layer
Business logic separated into services:
- `SessionStatusService` - Status transitions & validation
- `QuranSessionSchedulingService` - Session scheduling & conflict checking
- `AcademicSessionSchedulingService` - Academic session scheduling
- `SessionMeetingService` - LiveKit meeting management
- `AcademicSessionMeetingService` - Academic-specific meeting handling

---

## 13. IMPORTANT BUSINESS RULES

### 13.1 Subscription Counting
**Only these statuses count towards subscription:**
- `COMPLETED` - Session successfully completed
- `ABSENT` - Student marked absent (intentional design)

**Do NOT count:**
- `CANCELLED` - Teacher cancelled, not used
- `UNSCHEDULED` - Never scheduled
- `SCHEDULED` - Scheduled but not yet happened
- `READY` - Preparation phase
- `ONGOING` - Currently happening

**Critical Lock:** Uses `lockForUpdate()` to prevent race conditions on subscription_counted field.

### 13.2 Makeup Sessions
- Original session must be marked COMPLETED or ABSENT
- Can create makeup session: `createMakeupSession(scheduledAt, additionalData)`
- Makeup tracks parent via `makeup_session_for` relationship
- Used for absences or rescheduling

### 13.3 Teacher Availability
- System checks teacher has no conflicting sessions at scheduled time
- Conflict = any overlapping session based on duration
- Prevents double-booking

### 13.4 Timing Windows
**Before Session Starts (Preparation):**
- Teacher can join 30 minutes early
- Student can join 15 minutes early

**During Session:**
- Teachers/admins can join within wide window
- Students have grace period (default 15 min after start)

**After Session:**
- Teachers can join up to 2 hours after
- Students can join up to 30 minutes after

---

## 14. CRITICAL IMPLEMENTATION NOTES

### 14.1 Race Conditions
**Subscription Counting Fix:**
```php
\DB::transaction(function () use ($subscription) {
    $session = self::lockForUpdate()->find($this->id);
    
    // Check if already counted
    if (!$session->subscription_counted) {
        $subscription->useSession();
        $session->update(['subscription_counted' => true]);
    }
});
```

### 14.2 Meeting Room Closure (CRITICAL)
When session completes:
```php
// CRITICAL: Close meeting room to prevent new joins
if ($session->meeting_room_name) {
    $meetingService = app(\App\Services\SessionMeetingService::class);
    $meetingService->closeMeeting($session);
}
```

### 14.3 Attendance Source of Truth
**StudentSessionReport is PRIMARY source:**
```php
$studentReport = StudentSessionReport::where('session_id', $session->id)
    ->where('student_id', $session->student_id)
    ->first();

if ($studentReport && $studentReport->attendance_status === 'absent') {
    $session->update(['status' => SessionStatus::ABSENT]);
} 
// Only fallback to MeetingAttendance if no StudentSessionReport
```

### 14.4 Software Deletion
All session models use `SoftDeletes`:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

protected $dates = ['deleted_at'];
```
Allows recovery while logically removing from views.

---

## 15. CONTROLLERS & ROUTES

### Sessions Controllers
- `QuranSessionController` - Quran session CRUD & actions
- `AcademicSessionController` - Academic session CRUD & actions
- Additional controllers for scheduling, reporting, etc.

### Key Endpoints (Typical)
```
POST   /sessions                      - Create session
GET    /sessions/{id}                - View session
PUT    /sessions/{id}                - Update session
DELETE /sessions/{id}                - Soft delete

POST   /sessions/{id}/schedule       - Schedule session
POST   /sessions/{id}/start          - Start session
POST   /sessions/{id}/complete       - Complete session
POST   /sessions/{id}/cancel         - Cancel session
POST   /sessions/{id}/mark-absent    - Mark absent

POST   /sessions/{id}/homework       - Create homework
GET    /sessions/{id}/attendance     - View attendance

POST   /schedules                    - Create schedule
POST   /schedules/{id}/generate     - Generate sessions
```

---

## 16. KEY FILES SUMMARY

| File | Purpose |
|------|---------|
| `/app/Models/BaseSession.php` | Abstract base class for all sessions |
| `/app/Models/QuranSession.php` | Quran session implementation (1530 lines) |
| `/app/Models/AcademicSession.php` | Academic session implementation (550 lines) |
| `/app/Models/InteractiveCourseSession.php` | Interactive course session (455 lines) |
| `/app/Enums/SessionStatus.php` | Status enum with business logic |
| `/app/Services/SessionStatusService.php` | Status transitions & validation (510 lines) |
| `/app/Services/QuranSessionSchedulingService.php` | Session scheduling (200+ lines) |
| `/app/Services/AcademicSessionSchedulingService.php` | Academic scheduling |
| `/app/Services/SessionMeetingService.php` | LiveKit meeting management |
| `/app/Models/QuranSessionAttendance.php` | Quran attendance tracking |
| `/app/Models/StudentSessionReport.php` | Comprehensive session reports |
| `/database/migrations/2024_12_20_000001_refactor_quran_sessions_table.php` | Main sessions table structure |
| `/database/migrations/2025_09_01_150246_create_academic_sessions_table.php` | Academic table schema |
| `/database/migrations/2025_11_11_220307_create_interactive_session_attendances_table.php` | Interactive attendance |

---

## 17. RECOMMENDATIONS & NOTES

### For Development
1. Always use status validation before transitions
2. Test subscription counting with race conditions
3. Verify meeting rooms close properly on completion
4. Check StudentSessionReport is properly initialized
5. Validate teacher availability before scheduling

### For Troubleshooting
1. Check `status` and `scheduled_at` columns first
2. Verify `subscription_counted` flag for subscription issues
3. Review `meeting_room_name` for meeting problems
4. Check `StudentSessionReport` for attendance discrepancies
5. Look at `started_at` and `ended_at` for duration issues

### Performance Optimizations
1. Use eager loading for relationships (especially attendances)
2. Use `withCount()` for counting attendances
3. Add indexes for filtered queries (status, scheduled_at, etc.)
4. Cache session configuration for repeated access
5. Batch subscription updates for multiple sessions

---

**Document Generated:** 2025-11-12
**Last Updated:** When SessionStatus and service classes were analyzed
**Version:** 1.0 (Comprehensive Analysis)

