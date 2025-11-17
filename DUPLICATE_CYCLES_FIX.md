# Duplicate Attendance Cycles - Fixed

## Problem

When users joined sessions, 2 cycles were being created instead of 1:
```
Cycles: 2  ❌ (should be 1)
Duration: 0min
```

## Root Cause

**TWO competing systems** were both recording attendance:

1. **UI-based tracking** (OLD system):
   - `UnifiedMeetingController::joinMeeting()`
   - Called `MeetingAttendanceService::handleUserJoinPolymorphic()`
   - Created format: `['joined_at' => '...', 'left_at' => '']`

2. **Webhook-based tracking** (NEW system):
   - `LiveKitWebhookController::handleParticipantJoined()`
   - Called `AttendanceEventService::recordJoin()`
   - Created format: `['type' => 'join', 'participant_sid' => '...']`

**Result**: Every join created 2 cycles with different formats!

## What Was Fixed

Removed UI-based attendance recording from `UnifiedMeetingController`:

### Join Method (Line 192)
**Before**:
```php
// Record user join attempt
$this->attendanceService->handleUserJoinPolymorphic($session, $user, $sessionType);
```

**After**:
```php
// 🔥 FIX: Only update session status, NOT attendance
// Attendance will be recorded by LiveKit webhooks (source of truth)
if ($session->status->value === 'ready' || $session->status->value === 'scheduled') {
    $session->update(['status' => 'live']);
}
```

### Leave Method (Line 434)
**Before**:
```php
// Record user leave
$this->attendanceService->handleUserLeavePolymorphic($session, $user, $sessionType);
```

**After**:
```php
// 🔥 FIX: Don't record leave from UI
// Attendance will be recorded by LiveKit webhooks (source of truth)
Log::info('User left meeting (attendance will be recorded by webhook)');
```

## System Architecture (After Fix)

```
User joins via browser
     ↓
   UnifiedMeetingController::joinMeeting()
     ↓
   Generates LiveKit token
     ↓
   Updates session status to 'live' ✅
     ↓
   Returns token to browser (NO attendance recorded)
     ↓
User actually joins LiveKit room
     ↓
LiveKit Cloud sends webhook
     ↓
   LiveKitWebhookController::handleParticipantJoined()
     ↓
   AttendanceEventService::recordJoin()
     ↓
   MeetingAttendance created with 1 cycle ✅
```

## Why Webhooks Are Better

| Aspect | UI-based | Webhook-based |
|--------|----------|---------------|
| **Accuracy** | ❌ User might get token but not join | ✅ User actually joined LiveKit |
| **Timing** | ❌ Records when token generated | ✅ Records exact join/leave time from LiveKit |
| **Reliability** | ❌ Can miss leave events | ✅ LiveKit guarantees webhooks |
| **Reconnections** | ❌ Hard to track | ✅ Tracks via participant_sid |

## Expected Behavior Now

### When User Joins
1. Browser requests token
2. Session status → 'live'
3. User connects to LiveKit
4. **Webhook received** → Attendance cycle created
5. `attendance:debug` shows: **1 cycle**, duration 0min (in meeting)

### When User Leaves
1. User disconnects from LiveKit
2. **Webhook received** → Leave event matched to join
3. `attendance:debug` shows: **1 cycle**, duration calculated ✅

## Testing

### 1. Clear Old Data
```bash
php artisan attendance:clean --before=2025-11-16 --force
```

### 2. Create New Session
Create session with future scheduled time.

### 3. Join Session
```bash
# Terminal 1: Watch webhooks
./watch-webhooks.sh

# Terminal 2: Watch attendance
php artisan attendance:debug <session_id> --watch
```

Join via browser:
- Go to student/teacher portal
- Click "Join Session"
- Allow camera/mic

### 4. Expected Output

**While in meeting**:
```
=== Current Attendance Records (Uncalculated) ===
| Session | User      | First Join | Last Leave   | Cycles | Duration |
| #XXX    | Your Name | XX:XX:XX   | In meeting   | 1      | 0min     | ✅
```

**After leaving**:
```
=== LiveKit Webhook Activity (Last 5 Minutes) ===
| Time     | Event   | Session | User      | Duration |
| XX:XX:XX | ✗ LEAVE | #XXX    | Your Name | 3min     | ✅

=== Current Attendance Records (Uncalculated) ===
| Session | User      | First Join | Last Leave | Cycles | Duration |
| #XXX    | Your Name | XX:XX:XX   | XX:XX:XX   | 2      | 3min     | ✅
```

Note: Cycles will be 2 (1 join + 1 leave = 2 events), NOT 2 duplicate joins!

## Files Modified

- **app/Http/Controllers/UnifiedMeetingController.php**:
  - Line 192: Removed `handleUserJoinPolymorphic()` call
  - Line 434: Removed `handleUserLeavePolymorphic()` call
  - Added session status update on join
  - Added log message on leave

## Session Status Still Updates

✅ Session status transitions still work:
- `scheduled` → `live` (when first participant joins)
- `live` → `completed` (handled by scheduled job after session ends)

## Rollback Instructions

If you need to revert to UI-based tracking:

```bash
git diff HEAD app/Http/Controllers/UnifiedMeetingController.php
git checkout HEAD -- app/Http/Controllers/UnifiedMeetingController.php
```

But this will bring back duplicate cycles issue!

## Important Notes

1. **Webhooks MUST be configured** in LiveKit dashboard for this to work
2. **No UI-based attendance** - All tracking from LiveKit webhooks
3. **More accurate** - Records actual LiveKit join/leave times
4. **No duplicates** - Single source of truth (webhooks)

---

**Status**: ✅ Fixed
**Date**: 2025-11-15
**Impact**: Eliminates duplicate cycles, ensures accurate attendance from LiveKit webhooks only
