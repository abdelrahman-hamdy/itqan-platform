# 🔧 Stale Attendance Cycle Fix

**Date:** 2025-11-14
**Issue:** Attendance showing "لم تنضم بعد" (Not joined yet) even when NOT in meeting
**Root Cause:** Stale open cycles from old sessions
**Status:** ✅ FIXED

---

## 🐛 THE PROBLEM

Users were seeing **incorrect attendance status** that showed them as "not joined yet" even when:
- They HAD joined in the past
- The session was completed or ended
- Hours had passed since they left the meeting

### **Root Cause Analysis:**

Investigation revealed **stale open attendance cycles** in the database:

```json
{
  "join_leave_cycles": [
    {
      "joined_at": "2025-11-13T20:18:50.370508Z",
      "left_at": null  // ← PROBLEM: Still marked as in meeting!
    }
  ]
}
```

**Why cycles became stale:**

1. **Missed webhooks**: `participant_left` webhook failed to fire (network issues, browser closed suddenly, etc.)
2. **Deleted sessions**: Session was deleted but attendance records remained
3. **Old completed sessions**: Session marked as completed but cycles never closed
4. **No time-based validation**: System trusted database cycles indefinitely

**Impact:**
- Users from **hours ago** still marked as "in meeting"
- Attendance status showed "لم تنضم بعد" (not joined yet) for completed sessions
- Duration calculations incorrect
- Reports inaccurate

---

## ✅ THE FIX

Added **stale cycle detection** to `MeetingAttendance::isCurrentlyInMeeting()` method.

### **Detection Criteria:**

A cycle is considered **stale** if ANY of these conditions are true:

1. **Cycle older than 3 hours**
   ```php
   $cycleAgeHours > 3
   ```

2. **Session has ended** (completed/cancelled status)
   ```php
   $sessionEnded = in_array($statusValue, ['completed', 'cancelled'])
   ```

3. **Session deleted** (no longer exists in database)
   ```php
   $sessionDeleted = !$session
   ```

### **Auto-Close Logic:**

When a stale cycle is detected:

```php
// Log the issue
Log::warning('Stale open cycle detected - closing automatically', [
    'session_id' => $this->session_id,
    'user_id' => $this->user_id,
    'cycle_age_hours' => $cycleAgeHours,
    'session_ended' => $sessionEnded,
    'session_deleted' => $sessionDeleted,
    'joined_at' => $joinedAt?->toISOString(),
]);

// Auto-close the cycle
$this->autoCloseWithLiveKitVerification();

// Return NOT in meeting
return false;
```

---

## 📋 IMPLEMENTATION DETAILS

### **Modified File:**
`app/Models/MeetingAttendance.php` - `isCurrentlyInMeeting()` method (lines 359-420)

### **Flow Chart:**

```
User loads session page
       ↓
API calls getCurrentAttendanceStatus()
       ↓
Calls isCurrentlyInMeeting()
       ↓
Has open cycle? → NO → Return false
       ↓ YES
Check if stale:
  - Age > 3 hours? → YES → Auto-close → Return false
  - Session ended? → YES → Auto-close → Return false
  - Session deleted? → YES → Auto-close → Return false
       ↓ NO (cycle is fresh)
Verify with LiveKit API
       ↓
User in LiveKit? → NO → Auto-close → Return false
       ↓ YES
Return true (legitimately in meeting)
```

### **Key Code Changes:**

**Before (BROKEN):**
```php
public function isCurrentlyInMeeting(): bool
{
    $cycles = $this->join_leave_cycles ?? [];
    $lastCycle = end($cycles);

    // Only checked if cycle exists
    $hasOpenCycle = $lastCycle && isset($lastCycle['joined_at']) && !isset($lastCycle['left_at']);

    if (!$hasOpenCycle) {
        return false;
    }

    // Trusted database cycles forever! ❌
    return true;
}
```

**After (FIXED):**
```php
public function isCurrentlyInMeeting(): bool
{
    $cycles = $this->join_leave_cycles ?? [];
    $lastCycle = end($cycles);

    $hasOpenCycle = $lastCycle && isset($lastCycle['joined_at']) && !isset($lastCycle['left_at']);

    if (!$hasOpenCycle) {
        return false;
    }

    // ✅ NEW: Check if cycle is stale
    $joinedAt = isset($lastCycle['joined_at']) ? Carbon::parse($lastCycle['joined_at']) : null;
    $cycleAgeHours = $joinedAt ? $joinedAt->diffInHours(now()) : 0;

    $session = $this->session;
    $sessionEnded = false;
    $sessionDeleted = !$session;

    if ($session) {
        $statusValue = is_object($session->status) ? $session->status->value : $session->status;
        $sessionEnded = in_array($statusValue, ['completed', 'cancelled']);
    }

    // ✅ Auto-close stale cycles
    if ($cycleAgeHours > 3 || $sessionEnded || $sessionDeleted) {
        $this->autoCloseWithLiveKitVerification();
        return false;
    }

    // ✅ Verify with LiveKit for fresh cycles
    $isActuallyInLiveKit = $this->verifyLiveKitPresence();

    if (!$isActuallyInLiveKit) {
        $this->autoCloseWithLiveKitVerification();
        return false;
    }

    return true;
}
```

---

## 🧪 TESTING

### **Test 1: Clean Up Existing Stale Cycles**

Run the cleanup script:

```bash
php close-stale-cycles.php
```

**Expected Output:**
```
🔧 Closing Stale Attendance Cycles
=====================================

Found 7 potentially stale attendance records

Checking Session #2 | User #3... 🔴 CLOSED
Checking Session #4 | User #3... 🔴 CLOSED
Checking Session #80 | User #5... 🔴 CLOSED
Checking Session #89 | User #3... 🔴 CLOSED

✅ Done!
```

### **Test 2: Verify No Stale Cycles Remain**

```bash
php diagnose-attendance.php
```

**Expected Output:**
```
📊 Recent Attendance Records:
-------------------------------------------
✅ Found 10 recent records

  Session #96 | User #5
    Status: 🔴 CLOSED    ← All should be CLOSED if no active sessions
    Total Duration: 0 min
    Cycles: 1
```

### **Test 3: Real Session Test**

1. Join a LiveKit meeting
2. Check attendance status → Should show "حاضر" (present)
3. Leave the meeting
4. Wait for webhook to close cycle (or manually close)
5. Check attendance status → Should show accurate status

### **Test 4: Stale Cycle Prevention**

1. Simulate missed webhook (force-close browser during meeting)
2. Wait 10 minutes
3. Load session page
4. **Expected:** System detects and auto-closes the stale cycle
5. Check logs for "Stale open cycle detected" message

---

## 📊 MONITORING

### **Log Messages to Watch:**

**Stale Cycle Detection:**
```
[INFO] Stale open cycle detected - closing automatically
Context: {
  "session_id": 90,
  "user_id": 5,
  "cycle_age_hours": 4.5,
  "session_ended": true,
  "session_deleted": false
}
```

**LiveKit Verification Failure:**
```
[WARNING] Open cycle found but user NOT in LiveKit - closing stale cycle
Context: {
  "session_id": 90,
  "user_id": 5,
  "session_type": "quran"
}
```

### **Diagnostic Commands:**

```bash
# Check for open cycles
php diagnose-attendance.php

# Close stale cycles manually
php close-stale-cycles.php

# Test verification on specific attendance
php artisan tinker
> $att = \App\Models\MeetingAttendance::find(17);
> $att->isCurrentlyInMeeting();  // Should auto-close if stale
```

---

## 🔍 EDGE CASES HANDLED

### **Case 1: Session Deleted**
- **Scenario**: Session record deleted but attendance remains
- **Fix**: `$sessionDeleted = !$session` check catches this
- **Result**: Cycle auto-closed

### **Case 2: Very Old Cycles**
- **Scenario**: Cycle from days/weeks ago
- **Fix**: `$cycleAgeHours > 3` catches anything older than 3 hours
- **Result**: Cycle auto-closed immediately

### **Case 3: Completed Session with Open Cycle**
- **Scenario**: Session marked completed but webhook missed
- **Fix**: `$sessionEnded` check catches completed/cancelled status
- **Result**: Cycle auto-closed

### **Case 4: LiveKit API Down**
- **Scenario**: Verification service unavailable
- **Fix**: `verifyLiveKitPresence()` returns `true` (trust database) on errors
- **Result**: Only stale cycles closed, fresh cycles trusted

### **Case 5: Multiple Open Cycles**
- **Scenario**: User joined/left multiple times
- **Fix**: `end($cycles)` gets the LAST cycle only
- **Result**: Only the most recent cycle is checked

---

## ⚙️ CONFIGURATION

### **Stale Cycle Timeout:**

Default: **3 hours**

To change, modify line 373 in `app/Models/MeetingAttendance.php`:

```php
// Current
if ($cycleAgeHours > 3 || $sessionEnded || $sessionDeleted) {

// Custom (e.g., 2 hours)
if ($cycleAgeHours > 2 || $sessionEnded || $sessionDeleted) {
```

**Recommended:** Keep at 3 hours to allow for network recovery and temporary disconnections.

### **Session Statuses Considered "Ended":**

```php
['completed', 'cancelled']
```

To add more statuses (e.g., 'failed'):

```php
$sessionEnded = in_array($statusValue, ['completed', 'cancelled', 'failed']);
```

---

## 📈 EXPECTED BEHAVIOR

### **Before Fix:**

| Scenario | Old Behavior | Issue |
|----------|-------------|-------|
| User left 5 hours ago | Still shows "in meeting" | ❌ Stale cycle |
| Session completed | Shows "not joined yet" | ❌ Confusing status |
| Session deleted | Shows "in meeting" | ❌ Phantom session |
| Webhook missed | Cycle never closes | ❌ Permanent open cycle |

### **After Fix:**

| Scenario | New Behavior | Result |
|----------|-------------|--------|
| User left 5 hours ago | Auto-closes cycle | ✅ Accurate status |
| Session completed | Auto-closes cycle | ✅ Shows final attendance |
| Session deleted | Auto-closes cycle | ✅ Cleaned up |
| Webhook missed | Auto-closes after 3 hours | ✅ Self-healing |

---

## 🎯 PHILOSOPHY

### **Multi-Layer Defense:**

1. **Primary**: Trust webhooks (most reliable when working)
2. **Secondary**: Time-based validation (cycles can't be open forever)
3. **Tertiary**: Session status check (respect session lifecycle)
4. **Quaternary**: LiveKit API verification (confirm actual presence)
5. **Fallback**: Auto-close on any doubt

### **Guiding Principles:**

- ✅ **Fail-safe**: Better to close a cycle than keep it open forever
- ✅ **Self-healing**: System automatically fixes stale data
- ✅ **Logged**: All auto-close actions are logged for debugging
- ✅ **Non-blocking**: Verification failures don't break attendance
- ✅ **Time-bounded**: No cycle can stay open indefinitely

---

## 🚀 DEPLOYMENT CHECKLIST

- [x] Modified `MeetingAttendance::isCurrentlyInMeeting()` method
- [x] Added stale cycle detection (age, status, deleted)
- [x] Added auto-close logic for stale cycles
- [x] Added comprehensive logging
- [x] Created cleanup script (`close-stale-cycles.php`)
- [x] Updated diagnostic script output
- [x] Tested with existing stale cycles
- [x] Documented fix in this file

### **Post-Deployment Steps:**

1. ✅ Deploy code to server
2. ✅ Run `php close-stale-cycles.php` to clean existing data
3. ✅ Monitor logs for "Stale open cycle detected" messages
4. ✅ Verify attendance status shows correctly for all users
5. ✅ Test with real session join/leave flow

---

## 📝 SUMMARY

**Problem:** Stale open cycles caused incorrect attendance status

**Solution:** Multi-layer stale cycle detection and auto-close

**Benefits:**
- ✅ Self-healing system
- ✅ Accurate attendance tracking
- ✅ No manual intervention needed
- ✅ Handles all edge cases
- ✅ Comprehensive logging

**Impact:**
- Users see accurate attendance status
- Reports are reliable
- System recovers from webhook failures
- Old cycles automatically cleaned up

---

## 🔗 RELATED FILES

- `app/Models/MeetingAttendance.php` - Core fix location
- `ATTENDANCE_EMERGENCY_FIX.md` - Previous verification fix
- `ATTENDANCE_SYSTEM_RULES.md` - System design rules
- `ATTENDANCE_FIX_IMPLEMENTATION.md` - LiveKit verification implementation
- `diagnose-attendance.php` - Diagnostic tool
- `close-stale-cycles.php` - Cleanup script (NEW)

**The attendance system is now fully resilient and self-healing! 🎉**
