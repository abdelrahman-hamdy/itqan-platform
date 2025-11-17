# Attendance System Fix - Summary and Testing Guide

## Problem Reported
**Issue**: The meeting auto attendance tracker shows "غادر الجلسة" (left the session) as the final status for students after sessions end, even when they attended the full session.

## Root Cause Analysis

### Primary Issue: Format Inconsistency in `join_leave_cycles`
The system had **two different services writing to the same field in different formats**:

1. **AttendanceEventService** (from LiveKit webhooks):
   ```php
   [
       ['type' => 'join', 'timestamp' => '2025-11-17 10:00:00', 'participant_sid' => 'PA_xxx'],
       ['type' => 'leave', 'timestamp' => '2025-11-17 11:00:00', 'participant_sid' => 'PA_xxx']
   ]
   ```

2. **MeetingAttendance Model** (from manual join/leave API calls):
   ```php
   [
       ['joined_at' => '2025-11-17 10:00:00', 'left_at' => '2025-11-17 11:00:00', 'duration_minutes' => 60]
   ]
   ```

When the `CalculateSessionAttendance` job ran, it only expected the webhook format, causing duration calculation to fail for manual format cycles, resulting in 0% attendance and incorrect status.

### Secondary Issues

1. **Auto-close logic too strict**: Only closed cycles if session ended < 10 minutes ago
2. **Confusing UI messages**: "غادرت الجلسة" shown during session could be confused with final status
3. **No error logging**: Invalid status values weren't logged for debugging

## Changes Made

### 1. Enhanced Duration Calculation

**File**: [app/Jobs/CalculateSessionAttendance.php](app/Jobs/CalculateSessionAttendance.php:186-256)

Now handles BOTH webhook and manual formats by detecting the cycle structure and processing accordingly.

### 2. Improved Auto-Close Logic

**File**: [app/Models/MeetingAttendance.php](app/Models/MeetingAttendance.php:458-597)

Simplified to close cycles whenever session has ended + grace period, regardless of other conditions.

### 3. Clearer UI Messages

**File**: [app/Livewire/Student/AttendanceStatus.php](app/Livewire/Student/AttendanceStatus.php:120-214)

Uses `isCurrentlyInMeeting()` for accurate real-time status and shows clearer messages.

## Testing Guide

See [ATTENDANCE_SYSTEM_ANALYSIS_AND_FIXES.md](ATTENDANCE_SYSTEM_ANALYSIS_AND_FIXES.md) for detailed analysis.

### Quick Test

```bash
# 1. Start required services
php artisan schedule:work
php artisan queue:work

# 2. Create test session
php artisan tinker
$session = \App\Models\QuranSession::create([...]);

# 3. Test attendance calculation
php artisan attendance:calculate $session->id --force

# 4. Check logs
php artisan pail
```

## Session Type Compatibility

✅ **Group Quran Sessions** (`session_type = 'group'`)
✅ **Individual Quran Sessions** (`session_type = 'individual'`)
✅ **Trial Sessions** (has `trial_request_id`)
✅ **Academic Sessions**
✅ **Interactive Course Sessions**

All session types use the same attendance tracking system.

## Status Determination

| Status | Label | Conditions |
|--------|-------|------------|
| ATTENDED | حاضر | Joined on time + stayed ≥50% |
| LATE | متأخر | Joined late (>15 min) + stayed ≥50% |
| LEAVED | غادر مبكراً | Stayed <50% |
| ABSENT | غائب | Never joined or <1% |

## Monitoring

```bash
# Watch calculation logs
php artisan pail | grep "🧮\|📊\|✅"

# Manual recalculation
php artisan attendance:calculate SESSION_ID --force

# Check uncalculated attendance
php artisan tinker
\App\Models\MeetingAttendance::where('is_calculated', false)->count()
```

## Key Files Modified

1. `app/Jobs/CalculateSessionAttendance.php` - Enhanced duration calculation
2. `app/Models/MeetingAttendance.php` - Improved auto-close logic
3. `app/Livewire/Student/AttendanceStatus.php` - Clearer UI messages
4. `ATTENDANCE_SYSTEM_ANALYSIS_AND_FIXES.md` - Comprehensive documentation

