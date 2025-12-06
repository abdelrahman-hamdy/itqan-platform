# Sessions System - Quick Reference Guide

## Session Types

| Type | Use Case | Participants | Key Features |
|------|----------|--------------|--------------|
| **QuranSession - Individual** | One-on-one Quran lessons | 1 teacher + 1 student | Progress tracking, papers/verses, makeup sessions |
| **QuranSession - Circle** | Group Quran sessions | 1 teacher + multiple students | Auto-scheduling, monthly limits, group attendance |
| **AcademicSession - Individual** | Tutoring sessions | 1 teacher + 1 student | Grades, homework, Google Calendar sync |
| **AcademicSession - Course** | Class-based lessons | 1 teacher + multiple students | Course integration, materials, quizzes |
| **InteractiveCourseSession** | Online courses | Multiple students + teacher | Video completion, interactive exercises, engagement scoring |

## Status Lifecycle

```
UNSCHEDULED → SCHEDULED → READY → ONGOING → COMPLETED
                            ↓                    ↓
                         ABSENT (individual only)
                            ↓
                        CANCELLED (any status)
```

**Subscription Impact:**
- COMPLETED: Counts (session happened)
- ABSENT: Counts (student no-show)
- CANCELLED: Does NOT count (teacher cancelled)
- All other statuses: Do NOT count

## Key Models & Locations

### Session Models
```
app/Models/
├── BaseSession.php              (Abstract base, ~717 lines)
├── QuranSession.php             (Quran impl, ~1530 lines)
├── AcademicSession.php          (Academic impl, ~550 lines)
└── InteractiveCourseSession.php (Interactive impl, ~455 lines)
```

### Attendance Models
```
app/Models/
├── QuranSessionAttendance.php        (Quran-specific tracking)
├── AcademicSessionAttendance.php     (Academic tracking)
├── InteractiveSessionAttendance.php  (Course tracking)
└── StudentSessionReport.php          (PRIMARY SOURCE OF TRUTH)
```

### Status & Services
```
app/Enums/
└── SessionStatus.php (7 statuses with business logic)

app/Services/
├── SessionStatusService.php              (Status transitions)
├── QuranSessionSchedulingService.php    (Scheduling logic)
├── AcademicSessionSchedulingService.php (Academic scheduling)
├── SessionMeetingService.php             (LiveKit integration)
└── AcademicSessionMeetingService.php    (Academic meetings)
```

## Database Tables

### Main Tables
- `quran_sessions` - Quran session records
- `academic_sessions` - Academic session records
- `interactive_course_sessions` - Course session records

### Attendance Tables
- `quran_session_attendances` - Quick attendance tracking
- `academic_session_attendances` - Academic attendance
- `interactive_session_attendances` - Course attendance
- `student_session_reports` - **Primary attendance source** (teacher-verified)

### Scheduling Tables
- `session_schedules` - Custom schedules
- `quran_circle_schedules` - Group circle recurring schedules

## Most Important Fields

### Session Core
```php
$session->status              // SessionStatus enum
$session->session_code        // Unique per academy (e.g., "QSE-1-000001")
$session->scheduled_at        // When it should happen
$session->started_at          // When it actually started
$session->ended_at            // When it ended
$session->duration_minutes    // Scheduled duration
```

### Meeting Management
```php
$session->meeting_link        // Join URL (generated at READY)
$session->meeting_room_name   // LiveKit room identifier
$session->meeting_id          // LiveKit ID
$session->meeting_data        // Full API response (JSON)
```

### Attendance & Subscription
```php
$session->subscription_counted         // Boolean (prevent double-counting)
$session->attendance_status            // present/absent/late/left_early/partial
$session->participants_count           // Number who attended
```

### Type-Specific
```php
// Quran only
$session->papers_memorized_today
$session->recitation_quality           // 0-10
$session->is_makeup_session

// Academic only
$session->session_grade                // 0.0-10.0
$session->learning_outcomes            // Array

// Interactive only
$session->course_id
$session->session_number
```

## Common Operations

### Create & Schedule Session
```php
// Create (status: UNSCHEDULED)
$session = QuranSession::create([
    'academy_id' => 1,
    'title' => 'Quran Lesson',
    'duration_minutes' => 60,
    'session_type' => 'individual'
]);

// Schedule (status: SCHEDULED)
$schedulingService->scheduleIndividualSession(
    $session,
    Carbon::parse('2025-12-01 10:00')
);

// Auto-transitions:
// SCHEDULED → READY (~15 min before)
// READY → ONGOING (first participant joins)
// ONGOING → COMPLETED (auto, after duration + buffer)
```

### Mark Attendance
```php
// StudentSessionReport is PRIMARY source
$report = StudentSessionReport::where('session_id', $id)
    ->where('student_id', $studentId)
    ->first();

$report->update([
    'attendance_status' => 'present',
    'actual_attendance_minutes' => 58,
    'participation_score' => 8.5,
    'manually_overridden' => true,
    'overridden_by' => Auth::id()
]);
```

### Count Subscription
```php
// Automatic when session completes
if ($session->status === SessionStatus::COMPLETED) {
    $session->updateSubscriptionUsage();
    // Internally uses lockForUpdate() to prevent race conditions
}
```

### Cancel Session
```php
$statusService->transitionToCancelled(
    $session,
    'Teacher conflict',
    Auth::id()
);
// Does NOT count towards subscription
```

## Critical Implementation Points

### 1. Subscription Counting (Race Condition Prevention)
```php
// Always use locking to prevent duplicate counting
$session = self::lockForUpdate()->find($this->id);
if (!$session->subscription_counted) {
    $subscription->useSession();
    $session->update(['subscription_counted' => true]);
}
```

### 2. Meeting Room Closure (CRITICAL)
```php
// MUST close room when session completes
if ($session->meeting_room_name) {
    $meetingService->closeMeeting($session);
    // Prevents late students from joining
}
```

### 3. Attendance Source Priority
```php
// Always check StudentSessionReport FIRST
$studentReport = StudentSessionReport::find($session->id, $student->id);
if ($studentReport && $studentReport->attendance_status === 'absent') {
    // Use this data
}
// Only fallback to MeetingAttendance if no StudentSessionReport
```

### 4. Status Validation
```php
// Always validate status before transition
if (!$session->status->canComplete()) {
    return false;  // Cannot complete
}
```

## Useful Scopes

### By Status
```php
QuranSession::scheduled()      // status = SCHEDULED
QuranSession::ongoing()        // status = ONGOING
QuranSession::completed()      // status = COMPLETED
QuranSession::cancelled()      // status = CANCELLED
```

### By Time
```php
QuranSession::today()          // scheduled_at is today
QuranSession::upcoming()       // scheduled_at > now & status = SCHEDULED
QuranSession::past()           // scheduled_at < now
QuranSession::thisWeek()       // This week's sessions
```

### By Participant
```php
QuranSession::byTeacher($id)        // teacher sessions
QuranSession::byStudent($id)        // student sessions
QuranSession::individual()          // individual sessions
QuranSession::circle()              // circle sessions
```

## API Response Format (Typical)

```json
{
  "id": 1,
  "session_code": "QSE-1-000001",
  "status": "scheduled",
  "status_display": {
    "value": "scheduled",
    "label": "مجدولة",
    "color": "blue",
    "icon": "ri-calendar-line",
    "can_start": true,
    "can_cancel": true,
    "can_join": false
  },
  "title": "Quran Lesson",
  "scheduled_at": "2025-12-01T10:00:00Z",
  "started_at": null,
  "ended_at": null,
  "duration_minutes": 60,
  "meeting_link": null,
  "meeting_room_name": null,
  "teacher": {
    "id": 5,
    "name": "Ahmed Teacher",
    "email": "ahmed@example.com"
  },
  "student": {
    "id": 10,
    "name": "Mohamed Student",
    "email": "student@example.com"
  },
  "participation_count": 0,
  "created_at": "2025-11-12T00:00:00Z"
}
```

## Debugging Checklist

### Session Not Showing Meeting Link
- Check: status = READY (links only exist after READY)
- Check: meeting_room_name has value
- Check: meeting_link not null
- Check: meeting_expires_at > now

### Subscription Not Counting
- Check: session status is COMPLETED or ABSENT (only these count)
- Check: subscription_counted field = false (before counting)
- Check: StudentSessionReport.attendance_status is filled
- Check: No race condition - use lockForUpdate()

### Attendance Discrepancies
- Check: StudentSessionReport exists (primary source)
- Check: MeetingAttendance as fallback
- Check: join_time and leave_time recorded
- Check: auto_tracked = true if from LiveKit
- Check: manually_overridden flag for teacher corrections

### Sessions Not Auto-Transitioning
- Check: Cron job running: `php artisan schedule:work`
- Check: SessionStatusService being called
- Check: Session within time window for transition
- Check: No errors in logs (check laravel.log)

### Meeting Room Not Closing
- Check: closeMeeting() called in transitionToCompleted()
- Check: meeting_room_name has value
- Check: LiveKit service connected properly
- Check: Session status actually = COMPLETED

## Performance Tips

1. **Use eager loading:** `with(['attendances', 'studentReports'])`
2. **Use indexes:** Already defined on academy_id, status, scheduled_at
3. **Batch operations:** Use `processStatusTransitions()` for multiple sessions
4. **Cache configs:** Session configuration doesn't change per request
5. **Avoid N+1:** Always load relationships upfront

## Common Queries

### Find sessions needing action
```php
// Sessions ready to auto-complete
QuranSession::where('status', 'ongoing')
    ->where('scheduled_at', '<', now()->subMinutes(70))
    ->get();

// No-shows (individual sessions)
QuranSession::where('session_type', 'individual')
    ->where('status', 'ready')
    ->where('scheduled_at', '<', now()->addMinutes(15))
    ->whereDoesntHave('meetingAttendances')
    ->get();

// Teacher's upcoming sessions
QuranSession::where('quran_teacher_id', $teacherId)
    ->where('status', 'scheduled')
    ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
    ->orderBy('scheduled_at')
    ->get();
```

---

**Document Version:** 1.0  
**Last Updated:** 2025-11-12  
**For:** ITQAN Platform Sessions System
