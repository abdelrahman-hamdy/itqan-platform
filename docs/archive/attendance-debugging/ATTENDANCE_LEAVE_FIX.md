# ⚠️ DEPRECATED: Attendance Leave/Rejoin Fix

**Date:** 2025-11-14
**Status:** REPLACED BY WEBHOOK-BASED SYSTEM
**See:** `WEBHOOK_ATTENDANCE_SYSTEM.md` for current implementation

---

## 🔴 This Approach is Deprecated

This document describes a **manual API-based** attendance tracking system that has been **completely replaced** by a **webhook-based event log system**.

**Why deprecated:**
- ❌ Race conditions between frontend and backend
- ❌ Inaccurate timestamps (used `Carbon::now()` instead of LiveKit's exact times)
- ❌ Complex stale cycle detection logic
- ❌ Required frontend to manually call join/leave APIs
- ❌ No fallback for missed events

**New system benefits:**
- ✅ LiveKit webhooks are single source of truth
- ✅ Exact timestamps from LiveKit
- ✅ Zero frontend tracking code
- ✅ Idempotent webhook handling
- ✅ Reconciliation job for missed webhooks

**📖 Read `WEBHOOK_ATTENDANCE_SYSTEM.md` for current architecture.**

---

## Original Documentation (Historical Reference)

---

## 🐛 **Problem**

When a user:
1. Joined a meeting → Attendance started counting ✅
2. Left the meeting → Cycle NOT closed ❌
3. Refreshed page and tried to rejoin → **BLOCKED** ❌

**Error Message:**
```
⚠️ User already in meeting and attendance is being tracked, ignoring click
```

**Root Cause:**
- Database had an open cycle (`left_at: null`)
- System thought user was still in meeting
- Manual join API refused to add duplicate join
- Frontend blocked rejoin attempts

---

## ✅ **Solution**

### **1. Manual Leave API** (`routes/api.php`)

**Added:** `/api/sessions/meeting/leave` endpoint (lines 141-218)

**What it does:**
- Finds the open attendance cycle
- Sets `left_at` timestamp
- Calculates session duration
- Updates `total_duration_minutes`
- Clears cache for immediate UI update

**Code:**
```php
Route::post('/meeting/leave', function (Request $request) {
    $attendance = \App\Models\MeetingAttendance::where('session_id', $sessionId)
        ->where('user_id', $user->id)
        ->first();

    // Find open cycle
    $cycles = $attendance->join_leave_cycles ?? [];
    $lastCycleIndex = count($cycles) - 1;
    $lastCycle = $cycles[$lastCycleIndex] ?? null;

    if ($lastCycle && isset($lastCycle['joined_at']) && !isset($lastCycle['left_at'])) {
        // Close the cycle
        $cycles[$lastCycleIndex]['left_at'] = now()->toISOString();
        $attendance->join_leave_cycles = $cycles;

        // Calculate and update duration
        $joinedAt = \Carbon\Carbon::parse($lastCycle['joined_at']);
        $durationMinutes = $joinedAt->diffInMinutes(now());
        $attendance->total_duration_minutes += $durationMinutes;
        $attendance->save();

        // Clear cache
        \Cache::forget("attendance_status_{$sessionId}_{$user->id}");
    }

    return response()->json(['success' => true]);
});
```

---

### **2. Frontend Leave Handler** (`livekit-interface.blade.php`)

**Added:** `manuallyRecordLeave()` method (lines 3109-3150)

**What it does:**
- Called when LiveKit `disconnected` event fires
- Sends POST request to `/api/sessions/meeting/leave`
- Refreshes attendance status immediately
- Stops periodic polling (user left)

**Code:**
```javascript
async manuallyRecordLeave() {
    console.group('🎯 [ATTENDANCE] Manual Leave Fallback');
    console.log('Closing attendance cycle...');

    const response = await fetch('/api/sessions/meeting/leave', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
        },
        body: JSON.stringify({
            session_id: this.sessionId,
        }),
    });

    if (response.ok) {
        console.log('✅ Manual leave recorded successfully');
        await this.loadCurrentStatus();
        this.stopPeriodicUpdates();
    }

    console.groupEnd();
}
```

**Hook into LiveKit disconnect event:**
```javascript
room.on('disconnected', () => {
    console.log('📡 Disconnected from room');
    this.manuallyRecordLeave();
});
```

---

### **3. Smart Rejoin Logic** (`routes/api.php`)

**Updated:** Manual join API (lines 92-135)

**Before:**
```php
if ($hasOpenCycle) {
    // Always block if open cycle exists
    return response()->json(['message' => 'Already in meeting']);
}
```

**After:**
```php
if ($hasOpenCycle) {
    $joinedAt = \Carbon\Carbon::parse($lastCycle['joined_at']);
    $minutesAgo = $joinedAt->diffInMinutes(now());

    if ($minutesAgo < 1) {
        // Recent join - genuine duplicate, block it
        return response()->json(['message' => 'Already in meeting']);
    } else {
        // Stale cycle (user refreshed/reconnected) - close it and allow rejoin
        $cycles[$lastCycleIndex]['left_at'] = $joinedAt->copy()->addMinutes($minutesAgo)->toISOString();
        $attendance->join_leave_cycles = $cycles;
        $attendance->total_duration_minutes += $minutesAgo;
        $attendance->save();

        // Clear cache and proceed with new join
        \Cache::forget("attendance_status_{$session->id}_{$user->id}");
    }
}

// Create new cycle (either first time or after closing stale one)
$cycles[] = ['joined_at' => now()->toISOString(), 'left_at' => null];
```

**Logic:**
- Open cycle < 1 minute old → Block (genuine duplicate)
- Open cycle > 1 minute old → Close stale cycle, allow new join

---

## 🔄 **Complete Flow**

### **User Joins Meeting:**

```
1. User clicks "Join Meeting"
2. LiveKit connects successfully
3. room.on('connected') fires
4. Frontend calls manuallyRecordJoin()
5. POST /api/sessions/meeting/join
6. Backend creates open cycle: { joined_at: "2025-11-14T12:00:00Z", left_at: null }
7. Cache cleared
8. Frontend refreshes status
9. UI shows: "في الجلسة الآن" ✅
```

---

### **User Leaves Meeting:**

```
1. User closes tab / clicks Leave / loses connection
2. LiveKit disconnects
3. room.on('disconnected') fires
4. Frontend calls manuallyRecordLeave()
5. POST /api/sessions/meeting/leave
6. Backend closes cycle: { joined_at: "...", left_at: "2025-11-14T12:05:00Z" }
7. Duration calculated: 5 minutes
8. total_duration_minutes updated
9. Cache cleared
10. Frontend stops polling
11. UI shows final duration ✅
```

---

### **User Rejoins (Same Session):**

```
1. User clicks "Join Meeting" again
2. LiveKit connects
3. Frontend calls manuallyRecordJoin()
4. POST /api/sessions/meeting/join
5. Backend checks: "Do we have an open cycle?"
   - Cycle 1-2 seconds old? → Block (duplicate click)
   - Cycle > 1 minute old? → Close stale cycle, create new one
   - No cycle? → Create new one
6. New cycle created: { joined_at: "2025-11-14T12:10:00Z", left_at: null }
7. User can continue attending ✅
```

---

## 🧪 **Testing**

### **Test 1: Join → Leave → Rejoin**

1. Join meeting
   - **Expected:** Status = "في الجلسة الآن" ✅
   - **Expected:** Duration incrementing ✅

2. Leave meeting (close tab or click Leave)
   - **Expected Console:**
     ```
     📡 Disconnected from room
     🎯 [ATTENDANCE] Manual Leave Fallback
     ✅ Manual leave recorded successfully
     ```
   - **Expected DB:** Cycle closed with `left_at` timestamp

3. Rejoin meeting
   - **Expected:** No blocking error ✅
   - **Expected:** New cycle created ✅
   - **Expected:** Status = "في الجلسة الآن" again ✅

---

### **Test 2: Database Check**

```bash
php artisan tinker
```

```php
$att = \App\Models\MeetingAttendance::where('session_id', 99)
    ->where('user_id', 5)
    ->first();

echo "Total duration: " . ($att->total_duration_minutes ?? 0) . " minutes\n";
echo "Join count: " . ($att->join_count ?? 0) . "\n";
echo "Cycles:\n";
dd($att->join_leave_cycles);

// Expected output after join → leave → rejoin:
// Total duration: 5 minutes (from first session)
// Join count: 2
// Cycles: [
//   {
//     "joined_at": "2025-11-14T12:00:00Z",
//     "left_at": "2025-11-14T12:05:00Z"  // Closed cycle
//   },
//   {
//     "joined_at": "2025-11-14T12:10:00Z",
//     "left_at": null  // New open cycle
//   }
// ]
```

---

## 📊 **Before vs After**

| Scenario | Before | After |
|----------|--------|-------|
| **Join meeting** | ✅ Works | ✅ Works |
| **Leave meeting** | ❌ Cycle not closed | ✅ Cycle closed automatically |
| **Rejoin meeting** | ❌ Blocked with error | ✅ Works seamlessly |
| **Refresh during meeting** | ❌ Creates duplicate | ✅ Blocked (< 1 min) or closes stale cycle (> 1 min) |
| **Total duration** | ❌ Not tracked | ✅ Accumulated across sessions |
| **UI after leave** | ❌ Still shows "في الجلسة الآن" | ✅ Shows final duration |

---

## 🎯 **Key Benefits**

1. ✅ **Automatic leave tracking** - No manual intervention needed
2. ✅ **Seamless rejoining** - Users can join/leave/rejoin freely
3. ✅ **Accurate duration** - Tracks total time across multiple sessions
4. ✅ **Smart duplicate prevention** - Blocks rapid double-clicks but allows rejoins
5. ✅ **Cache management** - Always shows correct status
6. ✅ **Browser refresh handling** - Closes stale cycles automatically

---

## 🚀 **Production Ready**

- ✅ Works with or without webhooks
- ✅ Handles all edge cases (refresh, reconnect, multiple sessions)
- ✅ Accurate duration tracking
- ✅ Clean database state (no orphaned open cycles)
- ✅ Immediate UI updates (cache invalidation)

---

**Test now:**
1. Join a meeting
2. Leave (close tab or disconnect)
3. Rejoin the meeting
4. **Expected:** No errors, works perfectly! ✅
