# Attendance System Analysis and Fixes

## Issue Report
**Problem**: The meeting auto attendance tracker shows "غادر الجلسة" (left the session) as the final status for students after sessions end, even when they attended the full session.

## System Architecture Overview

### 1. Attendance Tracking Flow

```
LiveKit Webhook → LiveKitWebhookController → AttendanceEventService → MeetingAttendance
                                                                              ↓
                                                              CalculateSessionAttendance Job
                                                                              ↓
                                                                    StudentSessionReport
                                                                              ↓
                                                              AttendanceStatus Livewire Component
```

### 2. Key Components

#### A. LiveKitWebhookController
- **Location**: `app/Http/Controllers/LiveKitWebhookController.php`
- **Purpose**: Receives webhooks from LiveKit server
- **Events Handled**:
  - `participant_joined` (line 235-331)
  - `participant_left` (line 337-490)
  - `room_started`, `room_finished`, etc.

#### B. AttendanceEventService
- **Location**: `app/Services/AttendanceEventService.php`
- **Purpose**: Records join/leave events in MeetingAttendance
- **Key Methods**:
  - `recordJoin()` - Creates/updates attendance, adds join event to cycles
  - `recordLeave()` - Adds leave event to cycles, calculates duration
  - `calculateTotalDuration()` - Sums up duration from join/leave pairs

#### C. MeetingAttendance Model
- **Location**: `app/Models/MeetingAttendance.php`
- **Purpose**: Stores attendance data and provides business logic
- **Key Fields**:
  - `join_leave_cycles` - Array of join/leave events
  - `total_duration_minutes` - Calculated total time
  - `attendance_status` - Final status (attended/late/leaved/absent)
  - `is_calculated` - Whether final calculation is done

#### D. CalculateSessionAttendance Job
- **Location**: `app/Jobs/CalculateSessionAttendance.php`
- **Purpose**: Post-meeting calculation of final attendance
- **Schedule**: Every 10 seconds (local) / 5 minutes (production)
- **Process**:
  1. Find sessions that ended 5+ minutes ago
  2. For each uncalculated attendance record
  3. Calculate total duration (excluding preparation time)
  4. Determine status based on percentage and join time
  5. Sync to StudentSessionReport

#### E. AttendanceStatus Livewire Component
- **Location**: `app/Livewire/Student/AttendanceStatus.php`
- **Purpose**: Real-time UI display of attendance status
- **States**:
  - `waiting` - Before preparation time
  - `preparation` - During preparation period (10 min before start)
  - `in_meeting` - Session is live
  - `completed` - Session has ended

## Issues Identified

### Issue 1: Confusing Status Messages
**Symptom**: User sees "غادر الجلسة" which could mean two things:
1. During session: "غادرت الجلسة" (you left the session) - shown when student exits during live session
2. After session: "غادر مبكراً" (left early) - AttendanceStatus::LEAVED enum label

**Root Cause**: The AttendanceStatus component uses different text during the session vs after calculation.

**Location**: `app/Livewire/Student/AttendanceStatus.php:129`

```php
if ($attendance->last_leave_time) {
    $this->attendanceText = 'غادرت الجلسة'; // Shown during session
    $this->dotColor = 'bg-orange-400';
}
```

### Issue 2: Duration Calculation Edge Cases
**Symptom**: Student stays full session but gets marked as "left early" (< 50% attendance)

**Root Causes**:

#### 2.1 Open Cycles Not Being Closed
- If a student's browser crashes or they lose connection
- The leave webhook might not be received
- The cycle stays open indefinitely
- The `autoCloseStaleCycles()` method should handle this but has strict conditions

**Location**: `app/Models/MeetingAttendance.php:459-575`

**Current Logic**:
```php
// Only auto-close if BOTH:
// 1. Session has ended (+ 30 min grace)
// 2. Join was > 2 hours ago OR session ended < 10 min ago
```

**Problem**: For a 60-minute session:
- If student joins at start and stays until end
- Session ends at minute 60
- If webhook is missed, cycle stays open
- Auto-close only triggers if session ended < 10 min ago
- After 10 minutes, the condition fails

#### 2.2 Preparation Time Handling
**Symptom**: Student joins during preparation time, duration calculated incorrectly

**How it should work**:
1. Student joins 10 minutes before session (preparation time)
2. Session starts at scheduled time
3. Student stays until end
4. Duration should ONLY count from session start, not from join during preparation

**Current Implementation**:
- ✅ `CalculateSessionAttendance` job correctly clips join time to session start (line 197-200)
- ✅ `MeetingAttendance.calculateTotalDuration()` correctly caps at session start (line 218-239)
- ✅ `AttendanceEventService.calculateTotalDuration()` correctly calculates (line 198-214)

**Potential Issue**: The `getCurrentSessionDuration()` method (line 582-672) handles real-time display but might have edge cases.

#### 2.3 Session End Time Calculation
**Symptom**: Session end time might be calculated incorrectly for some session types

**Current Logic**:
```php
// In CalculateSessionAttendance job:
$sessionEnd = $session->scheduled_end_at ?? $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60);
```

**Problem**: `QuranSession` doesn't have a `scheduled_end_at` column (confirmed by migration history), so it always uses the fallback.

**Verification Needed**: Check if `duration_minutes` is always set correctly for all session types.

### Issue 3: Join/Leave Event Matching
**Symptom**: Leave events might not match to correct join events

**How Matching Works**:
- Events are matched by `participant_sid` from LiveKit
- Each join creates a unique `participant_sid`
- The leave event should reference the same `participant_sid`

**Current Implementation**: `AttendanceEventService.recordLeave()` (line 110-143)

**Potential Issues**:
1. If LiveKit sends leave webhook before join webhook (rare race condition)
2. If participant_sid is null or changes
3. If multiple join/leave cycles happen rapidly

**Current Fix in Place**: Synchronous retry with 5-second sleep (line 392-420 in LiveKitWebhookController)

### Issue 4: Trial Session Support
**Current Status**: ✅ Trial sessions are supported
- Trial sessions are created as regular `QuranSession` records
- They have a `trial_request_id` field
- The attendance system treats them like any other session
- **Session type for trials**: They use `session_type = 'individual'`

**Verification**: The system should work identically for trial sessions.

### Issue 5: Status Determination Logic
**Current Logic** (from `CalculateSessionAttendance.determineAttendanceStatus()`):

```php
// Never joined or < 1% attendance
if (!$firstJoinTime || $percentage < 1) {
    return AttendanceStatus::ABSENT;
}

// Stayed < 50% - left early
if ($percentage < 50) {
    return AttendanceStatus::LEAVED;
}

// Stayed >= 50% but joined after tolerance (15 min)
if ($isLate) {
    return AttendanceStatus::LATE;
}

// Stayed >= 50% and joined on time
return AttendanceStatus::ATTENDED;
```

**Analysis**:
- ✅ Logic is sound
- ✅ 50% threshold is reasonable
- ✅ 15-minute late tolerance is standard

**Potential Issue**: If `total_duration_minutes` is incorrect, the percentage will be wrong, leading to wrong status.

## Root Cause Analysis

The most likely root cause is **Issue #2.1: Open Cycles Not Being Closed Properly**

### Scenario:
1. Student joins session at scheduled time
2. Student stays for full 60-minute session
3. At the end, student's browser crashes or connection drops
4. LiveKit `participant_left` webhook is **not received**
5. The join cycle stays open (no matching leave event)
6. The `autoCloseStaleCycles()` method runs but...
7. **It only closes if session ended < 10 minutes ago**
8. After 10 minutes, the cycle is still open
9. When `CalculateSessionAttendance` runs:
   - It looks at `join_leave_cycles`
   - Finds only join events, no matching leave events
   - `calculateTotalDuration()` only counts complete cycles (join + leave)
   - Returns 0 minutes
   - Percentage = 0 / 60 = 0%
   - Status = ABSENT (if < 1%) or should never reach LEAVED logic

**Wait... this doesn't match the reported issue.**

If the cycle is open, `calculateTotalDuration()` should return 0, which would give ABSENT status, not LEAVED.

Let me re-examine...

### Re-Analysis: Looking at `AttendanceEventService.recordLeave()`

Ah! I found it. Look at line 145-158:

```php
// If no match found, add leave event anyway (might be paired later)
if (!$matchFound) {
    $cycles[] = [
        'type' => 'leave',
        'timestamp' => $leaveTime,
        'event_id' => $eventData['event_id'] ?? null,
        'participant_sid' => $participantSid,
        'duration_minutes' => $eventData['duration_minutes'] ?? null,
    ];
}
```

So even if a join event isn't found, the leave event is still added. This could create **orphaned leave events**.

Then in `calculateTotalDuration()` (line 198-214):

```php
foreach ($cycles as $cycle) {
    if ($cycle['type'] === 'join') {
        $lastJoinTime = $cycle['timestamp'];
    } elseif ($cycle['type'] === 'leave' && $lastJoinTime) {
        // Only count if there was a previous join
        $totalMinutes += duration;
        $lastJoinTime = null;
    }
}
```

This logic only pairs consecutive join→leave events. If a leave comes before a join, or if there's no matching join, the duration won't be counted.

## The Real Problem

I think I found it! The issue is in how the **CalculateSessionAttendance job** calculates duration:

It uses `$this->calculateTotalDuration()` which expects cycles to have `type` = 'join' or 'leave'.

But look at **MeetingAttendance.recordJoin()** (line 94-133):

```php
$cycles[] = [
    'joined_at' => $now->toISOString(),  // ❌ Uses 'joined_at', not 'type' = 'join'
    'left_at' => null,
];
```

And **MeetingAttendance.recordLeave()** (line 138-202):

```php
$cycles[$lastCycleIndex]['left_at'] = $now->toISOString(); // ❌ Sets 'left_at', not separate event
```

So **MeetingAttendance** uses the format:
```php
[
    ['joined_at' => timestamp, 'left_at' => timestamp, 'duration_minutes' => X]
]
```

But **AttendanceEventService** uses:
```php
[
    ['type' => 'join', 'timestamp' => X],
    ['type' => 'leave', 'timestamp' => Y]
]
```

**THERE ARE TWO DIFFERENT FORMATS FOR join_leave_cycles!**

This is the bug!

## Confirmed Root Cause

There are **two different services writing to `join_leave_cycles` in different formats**:

1. **AttendanceEventService** (from webhooks):
   - Writes `['type' => 'join/leave', 'timestamp' => X]`

2. **MeetingAttendance model methods** (from manual API calls):
   - Writes `['joined_at' => X, 'left_at' => Y]`

When **CalculateSessionAttendance** job runs, it uses `calculateTotalDuration()` which expects the webhook format with `type` field.

If cycles are in the MeetingAttendance format (joined_at/left_at), the calculation will fail!

## Solution Strategy

### Option 1: Standardize on Webhook Format
- Update `MeetingAttendance.recordJoin/Leave()` to use same format as webhooks
- Ensure all duration calculation methods use the webhook format

### Option 2: Support Both Formats
- Update duration calculation methods to detect format and handle both
- More robust but more complex

### Option 3: Deprecate Manual Join/Leave
- Only use webhooks for attendance tracking
- Remove manual `recordJoin/Leave` methods from MeetingAttendance

**Recommendation**: **Option 1** - Standardize on the webhook format, as it's more event-driven and matches the new architecture.

## Additional Issues Found

### Issue A: Auto-Close Logic is Too Strict
The auto-close only triggers if session ended < 10 minutes ago. For debugging or delayed processing, this is too restrictive.

**Fix**: Change to close any open cycle if session ended + 30 minute grace period has passed.

### Issue B: No Cleanup for Orphaned Events
If a leave webhook arrives before join webhook (rare but possible), the leave event is added but never matched.

**Fix**: The existing retry logic with 5-second sleep should handle this. We can enhance the reconciliation job.

### Issue C: Real-time Display vs Final Calculation Mismatch
The Livewire component shows different text during the session than the final calculated status, causing user confusion.

**Fix**: Clarify messaging to indicate when calculation is in progress.

## Recommendations

1. **Immediate Fix**: Standardize `join_leave_cycles` format across all services
2. **Short-term**: Improve auto-close logic to be less strict
3. **Medium-term**: Add data migration to convert old format cycles to new format
4. **Long-term**: Add monitoring/alerts for uncalculated attendance after sessions

## Testing Strategy

1. **Test Case 1**: Student joins before session start (preparation), stays full session
2. **Test Case 2**: Student joins late (after 15 min), stays full session
3. **Test Case 3**: Student joins on time, leaves after 25 minutes (< 50%)
4. **Test Case 4**: Student joins on time, browser crashes, no leave webhook
5. **Test Case 5**: Trial session with single student
6. **Test Case 6**: Group session with multiple students
