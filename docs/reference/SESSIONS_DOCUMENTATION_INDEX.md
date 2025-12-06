# Sessions System Documentation Index

This directory contains comprehensive documentation for the ITQAN Platform Sessions System.

## Documentation Files

### 1. **SESSIONS_SYSTEM_ANALYSIS.md** (33 KB)
   **Complete Technical Reference**
   
   A comprehensive 17-section analysis covering every aspect of the sessions system:
   - Architecture overview and design patterns
   - Detailed model descriptions (BaseSession, QuranSession, AcademicSession, InteractiveCourseSession)
   - All session relationships and relationships
   - Complete status transition workflows
   - Attendance tracking system (including StudentSessionReport as primary source)
   - LiveKit meeting integration details
   - Database schema and migrations
   - Session scheduling and generation logic
   - Status management service
   - Homework and assignment systems
   - Session reporting structure
   - Key design patterns used
   - Critical business rules
   - Implementation notes with code examples
   - Controllers and routes
   - File locations and summary
   
   **Best for:** Deep understanding, troubleshooting, implementation planning

---

### 2. **SESSIONS_ARCHITECTURE_DIAGRAM.txt** (21 KB)
   **Visual Architecture Reference**
   
   ASCII diagrams and flowcharts showing:
   - Model inheritance hierarchy
   - Session status flow diagram
   - Database tables organization
   - Session lifecycle workflow
   - Attendance tracking workflow
   - LiveKit meeting integration process
   - Service responsibilities and structure
   - Critical business rules
   - Field organization by session type
   - SessionStatus enum details
   - Relationship diagrams for QuranSession
   
   **Best for:** Visual learners, architecture overview, presentations

---

### 3. **SESSIONS_QUICK_REFERENCE.md** (10 KB)
   **Developer Quick Reference**
   
   Concise guide for developers including:
   - Session types comparison table
   - Status lifecycle diagram
   - Key models and their locations
   - Most important fields reference
   - Common operations with PHP code examples
   - Critical implementation points
   - Database scopes for queries
   - API response format example
   - Debugging checklist
   - Performance optimization tips
   - Common database queries
   
   **Best for:** Quick lookup, code reference, debugging

---

### 4. **SESSION_COUNT_AND_CALCULATION_FIX.md** (14 KB)
   **Previous Analysis - Session Counting Issues**
   
   Historical documentation about subscription counting fixes and session calculations.
   
   **Best for:** Understanding past issues and solutions

---

### 5. **PHASE5_SESSION_ANALYSIS.md** (12 KB)
   **Previous Analysis - Session System Phase 5**
   
   Earlier phase analysis documentation.
   
   **Best for:** Historical context and evolution of the system

---

## Quick Navigation

### By Use Case

**I want to understand the architecture:**
→ Start with SESSIONS_ARCHITECTURE_DIAGRAM.txt, then read SESSIONS_SYSTEM_ANALYSIS.md sections 1-4

**I need to implement a feature:**
→ SESSIONS_QUICK_REFERENCE.md for APIs, then SESSIONS_SYSTEM_ANALYSIS.md for details

**I'm debugging an issue:**
→ SESSIONS_QUICK_REFERENCE.md debugging checklist, then specific sections in SESSIONS_SYSTEM_ANALYSIS.md

**I need to optimize queries:**
→ SESSIONS_QUICK_REFERENCE.md performance tips, then SESSIONS_SYSTEM_ANALYSIS.md sections 7

**I want to understand status transitions:**
→ SESSIONS_ARCHITECTURE_DIAGRAM.txt section 2 & 4, then SESSIONS_SYSTEM_ANALYSIS.md sections 3-4

**I need attendance tracking details:**
→ SESSIONS_SYSTEM_ANALYSIS.md section 5, then SESSIONS_ARCHITECTURE_DIAGRAM.txt section 5

---

## Key Concepts at a Glance

### Session Types
- **QuranSession** - Islamic Quran teaching (individual/circle/trial/assessment)
- **AcademicSession** - Academic tutoring (individual/course-based)
- **InteractiveCourseSession** - Online courses with engagement tracking

### Status Lifecycle
```
UNSCHEDULED → SCHEDULED → READY → ONGOING → COMPLETED
                            ↓                    ↓
                         ABSENT (individual)  [COUNTS SUBSCRIPTION]
                            ↓
                        CANCELLED (any) [DOES NOT COUNT]
```

### Critical Rules
1. **Subscription Counting**: Only COMPLETED and ABSENT count
2. **Attendance Source**: StudentSessionReport is PRIMARY (teacher-verified)
3. **Meeting Closure**: CRITICAL - must close when session completes
4. **Race Conditions**: Use lockForUpdate() for subscription counting
5. **Teacher Availability**: Prevents overlapping session scheduling

### Most Important Fields
- `status` - SessionStatus enum (drives all logic)
- `scheduled_at` - When session should happen
- `meeting_link` - Join URL (generated at READY status)
- `subscription_counted` - Boolean (prevents double-counting)
- `StudentSessionReport` - Comprehensive attendance record

---

## File Locations in Codebase

### Models
```
app/Models/
├── BaseSession.php              (Abstract base)
├── QuranSession.php             (Quran implementation)
├── AcademicSession.php          (Academic implementation)
├── InteractiveCourseSession.php  (Interactive implementation)
├── QuranSessionAttendance.php    (Quran attendance)
├── AcademicSessionAttendance.php (Academic attendance)
├── InteractiveSessionAttendance.php (Interactive attendance)
└── StudentSessionReport.php      (PRIMARY ATTENDANCE SOURCE)
```

### Services
```
app/Services/
├── SessionStatusService.php              (Status transitions)
├── QuranSessionSchedulingService.php    (Scheduling)
├── AcademicSessionSchedulingService.php (Academic scheduling)
├── SessionMeetingService.php             (LiveKit integration)
└── AcademicSessionMeetingService.php    (Academic meetings)
```

### Status & Enums
```
app/Enums/
└── SessionStatus.php (7 statuses with business logic)
```

### Migrations
```
database/migrations/
├── 2024_12_20_000001_refactor_quran_sessions_table.php
├── 2025_09_01_150246_create_academic_sessions_table.php
├── 2025_11_10_063351_create_academic_session_attendances_table.php
├── 2025_11_10_063633_enhance_interactive_session_attendances_table.php
├── 2025_11_11_220307_create_interactive_session_attendances_table.php
├── 2025_11_11_220308_create_interactive_session_reports_table.php
└── ... (15+ more migration files)
```

---

## Common Queries & Operations

### Find Sessions Ready for Action
```php
// Sessions ready to auto-complete
QuranSession::where('status', 'ongoing')
    ->where('scheduled_at', '<', now()->subMinutes(70))
    ->get();

// Teacher's upcoming sessions
QuranSession::where('quran_teacher_id', $id)
    ->where('status', 'scheduled')
    ->upcoming()
    ->get();
```

### Attendance Management
```php
// Primary source - StudentSessionReport
$report = StudentSessionReport::where('session_id', $id)
    ->where('student_id', $studentId)
    ->first();

// Update with teacher verification
$report->update([
    'attendance_status' => 'present',
    'manually_overridden' => true,
    'overridden_by' => Auth::id()
]);
```

### Subscription Counting
```php
// Automatic in session completion
if ($session->status === SessionStatus::COMPLETED) {
    $session->updateSubscriptionUsage(); // Uses lockForUpdate() internally
}
```

---

## Critical Implementation Points

### 1. Status Validation
Always check status before transitions:
```php
if (!$session->status->canComplete()) {
    return false;
}
```

### 2. Meeting Room Closure
MUST close rooms when sessions complete:
```php
if ($session->meeting_room_name) {
    $meetingService->closeMeeting($session);
}
```

### 3. Attendance Priority
Check StudentSessionReport FIRST:
```php
$report = StudentSessionReport::find(...);
if ($report) {
    // Use this (teacher-verified)
} else {
    // Fallback to MeetingAttendance
}
```

### 4. Race Condition Prevention
Use locking for subscription counting:
```php
$session = self::lockForUpdate()->find($id);
if (!$session->subscription_counted) {
    // Count towards subscription
}
```

---

## Performance Optimization

1. **Eager Load Relationships**: `with(['attendances', 'studentReports'])`
2. **Use Existing Indexes**: academy_id, status, scheduled_at, etc.
3. **Batch Operations**: `processStatusTransitions()` for multiple sessions
4. **Cache Configuration**: Session config doesn't change per request
5. **Avoid N+1 Queries**: Load relationships upfront

---

## Debugging Help

### Session Not Showing Meeting Link
- Check: status = READY (only then links exist)
- Check: meeting_room_name not null
- Check: meeting_expires_at > now

### Subscription Not Counting
- Check: status is COMPLETED or ABSENT
- Check: subscription_counted = false
- Check: StudentSessionReport attendance_status is set

### Attendance Discrepancies
- Check: StudentSessionReport exists (primary source)
- Check: join_time and leave_time recorded
- Check: manually_overridden flag for teacher corrections

### Sessions Not Auto-Transitioning
- Check: Laravel cron job running
- Check: SessionStatusService being called
- Check: No errors in laravel.log

---

## Document Maintenance

**Last Updated:** 2025-11-12  
**Version:** 1.0  
**Scope:** Complete ITQAN Platform Sessions System  
**Coverage:** Models, Services, Database, Business Logic, Implementation

---

## Getting Started

1. **New to the system?** 
   → Read SESSIONS_ARCHITECTURE_DIAGRAM.txt first

2. **Need to implement a feature?**
   → Check SESSIONS_QUICK_REFERENCE.md then SESSIONS_SYSTEM_ANALYSIS.md

3. **Debugging an issue?**
   → Use debugging checklist in SESSIONS_QUICK_REFERENCE.md

4. **Want deep understanding?**
   → Read SESSIONS_SYSTEM_ANALYSIS.md sections 1-6

---

**For questions or updates to this documentation, refer to the main SESSIONS_SYSTEM_ANALYSIS.md file.**
