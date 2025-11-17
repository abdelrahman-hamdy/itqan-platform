# 🚨 CRITICAL: Attendance Tracking Complete Solution

**Issue Date:** 2025-11-13
**Severity:** CRITICAL
**Status:** Solution Provided - Awaiting Implementation

---

## 🐛 **PROBLEM #2: Attendance Tracks Page Presence, Not Actual Meeting Time**

### What's Happening Now

The system is currently tracking attendance in **TWO WAYS** simultaneously:

#### ❌ **Method 1: Frontend Manual Tracking** (BUGGY - Current Implementation)
```javascript
// Line 2700 in livekit-interface.blade.php
if (attendanceTracker) {
    setTimeout(() => {
        attendanceTracker.recordJoin(); // ❌ Called when button clicked, not when joined meeting!
    }, 1000);
}
```

**Flow:**
1. User loads session page → page loads
2. User clicks "Start Meeting" button → `recordJoin()` called
3. User is marked as "in meeting" **even before connecting to LiveKit**
4. Attendance minutes start incrementing **while on page, not in actual meeting**
5. User could close video connection but stay on page → still counted as attending!

#### ✅ **Method 2: LiveKit Webhooks** (CORRECT - Already Exists But Not Used Exclusively)
```php
// LiveKitWebhookController.php
private function handleParticipantJoined(array $data): void
{
    // LiveKit server automatically sends webhook when user ACTUALLY joins
    $this->attendanceService->handleUserJoin($session, $user);
}
```

**Flow:**
1. User clicks "Start Meeting"
2. LiveKit room connects
3. LiveKit SERVER sends webhook when participant **actually** joins room
4. Backend records attendance based on **actual presence in video meeting**
5. LiveKit SERVER sends webhook when participant leaves
6. Backend calculates accurate duration

---

## ✅ **SOLUTION: Use ONLY LiveKit Webhooks for Attendance**

### Why This is the Correct Approach

| Aspect | Frontend Tracking | LiveKit Webhooks |
|--------|------------------|------------------|
| **Tracks** | Page presence | Actual meeting presence |
| **Accuracy** | ❌ Can be gamed | ✅ Server-authoritative |
| **Reliability** | ❌ Depends on JS running | ✅ Automatic |
| **Security** | ❌ Can be manipulated | ✅ Can't be tampered with |
| **Works if browser crashes** | ❌ No | ✅ Yes |
| **Tracks actual video time** | ❌ No | ✅ Yes |

---

## 🔧 **IMMEDIATE FIX: Disable Frontend Attendance Tracking**

### Step 1: Comment Out Frontend `recordJoin()` Calls

**File:** `resources/views/components/meetings/livekit-interface.blade.php`

#### Change 1: Remove Auto-Record on Meeting Start (Line ~2696-2702)
```javascript
// BEFORE (BUGGY):
// CRITICAL FIX: Immediately record join when meeting starts
if (attendanceTracker) {
    console.log('🎯 Recording join immediately after meeting start');
    setTimeout(() => {
        attendanceTracker.recordJoin();
    }, 1000);
}

// AFTER (FIXED):
// REMOVED: Frontend attendance tracking disabled
// Attendance is now tracked exclusively via LiveKit webhooks
// This ensures we only count time in ACTUAL meeting, not time on page
if (attendanceTracker) {
    console.log('🎯 Attendance will be tracked via LiveKit webhooks only');
    // recordJoin() call removed - webhooks handle this
}
```

#### Change 2: Remove Fallback RecordJoin (Line ~3353-3358)
```javascript
// BEFORE (BUGGY):
if (!room) {
    console.warn('⚠️ Room not available, trying to connect anyway...');
    // Fallback: try to record join immediately since user clicked to join
    setTimeout(() => {
        console.log('🔄 Fallback: Recording join after timeout');
        this.recordJoin();
    }, 2000);
    return;
}

// AFTER (FIXED):
if (!room) {
    console.warn('⚠️ Room not available, waiting for connection...');
    // REMOVED: No fallback join recording
    // LiveKit webhooks will handle attendance when room actually connects
    return;
}
```

#### Change 3: Remove Direct RecordJoin Calls (Lines ~3366, 3372, 3386)
```javascript
// BEFORE (BUGGY):
if (room.state === 'connected') {
    console.log('📡 Room already connected - recording join immediately');
    this.recordJoin();
}

room.on('connected', () => {
    console.log('📡 Connected to room - recording join');
    this.recordJoin();
});

room.on('connectionStateChanged', (state) => {
    console.log('📡 Connection state changed:', state);
    if (state === 'connected') {
        this.recordJoin();
    } else if (state === 'disconnected' || state === 'failed') {
        this.recordLeave();
    }
});

// AFTER (FIXED):
if (room.state === 'connected') {
    console.log('📡 Room already connected - webhooks will track attendance');
    // this.recordJoin(); // REMOVED
}

room.on('connected', () => {
    console.log('📡 Connected to room - webhooks will track attendance');
    // this.recordJoin(); // REMOVED
});

room.on('connectionStateChanged', (state) => {
    console.log('📡 Connection state changed:', state);
    // Attendance tracking removed - webhooks handle this
    // if (state === 'connected') {
    //     this.recordJoin();
    // } else if (state === 'disconnected' || state === 'failed') {
    //     this.recordLeave();
    // }
});
```

### Step 2: Keep Frontend Polling for DISPLAY Only

The frontend polling (`/api/sessions/{id}/attendance-status`) should REMAIN active for **displaying** current attendance status, but NOT for recording it:

```javascript
// This is OK - it READS attendance data for display
async loadCurrentStatus() {
    const response = await fetch(`/api/sessions/${this.sessionId}/attendance-status`);
    const data = await response.json();
    this.updateAttendanceUI(data); // ✅ Just updates UI
}

// Start periodic updates every 30 seconds for display
startPeriodicUpdates() {
    this.updateInterval = setInterval(() => {
        this.loadCurrentStatus(); // ✅ Reading only, not writing
    }, 30000);
}
```

### Step 3: Ensure LiveKit Webhooks are Working

**Verify webhooks are configured:**

**File:** `config/livekit.php`
```php
'webhook' => [
    'url' => env('LIVEKIT_WEBHOOK_URL', 'https://yourdomain.com/api/livekit/webhook'),
    'secret' => env('LIVEKIT_WEBHOOK_SECRET'),
],
```

**File:** `.env`
```env
LIVEKIT_WEBHOOK_URL=https://itqan-platform.test/api/livekit/webhook
LIVEKIT_WEBHOOK_SECRET=your-webhook-secret-key
```

**Test webhooks are working:**
```bash
# Monitor logs for webhook events
php artisan pail --filter="LiveKit webhook"

# Should see logs like:
# "Participant joined session"
# "Participant left session"
```

---

## 🚀 **RECOMMENDED ENHANCEMENTS**

Now let me explain the three enhancements I mentioned earlier with FULL implementation code:

---

### **Enhancement #1: Replace Polling with WebSockets (Laravel Echo/Reverb)**

#### **What It Is**
Instead of frontend calling `/api/sessions/{id}/attendance-status` every 30 seconds:
- Backend PUSHES attendance updates to frontend in real-time
- Uses Laravel Reverb (WebSocket server built into Laravel 11)
- Zero polling overhead, instant updates

#### **Why It's Better**
- ✅ **95% reduction in API calls** (from 120/hour to ~0)
- ✅ **Instant updates** (no 30-second delay)
- ✅ **Better performance** (less server load)
- ✅ **Real-time sync** (all users see updates simultaneously)

#### **How It Works**

```
┌──────────┐                    ┌──────────┐                    ┌──────────┐
│ LiveKit  │                    │  Backend │                    │ Frontend │
│  Server  │                    │  Laravel │                    │  Browser │
└──────────┘                    └──────────┘                    └──────────┘
     │                                │                                │
     │ participant_joined webhook     │                                │
     ├───────────────────────────────>│                                │
     │                                │                                │
     │                                │  Store attendance in DB        │
     │                                │  (handleUserJoin)              │
     │                                │                                │
     │                                │  Broadcast event via WebSocket │
     │                                ├───────────────────────────────>│
     │                                │                                │
     │                                │                          Update UI
     │                                │                          (no API call!)
```

#### **Full Implementation**

##### **Step 1: Create Attendance Event**

**File:** `app/Events/AttendanceUpdated.php` (NEW)
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $sessionId;
    public int $userId;
    public array $attendanceData;

    public function __construct(int $sessionId, int $userId, array $attendanceData)
    {
        $this->sessionId = $sessionId;
        $this->userId = $userId;
        $this->attendanceData = $attendanceData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('session.' . $this->sessionId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'attendance' => $this->attendanceData,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

##### **Step 2: Broadcast Event When Attendance Changes**

**File:** `app/Services/MeetingAttendanceService.php` (ADD after line 47)
```php
public function handleUserJoin(MeetingCapable $session, User $user): bool
{
    try {
        // ... existing join logic ...

        $joinSuccess = $attendance->recordJoin();

        if (!$joinSuccess) {
            return false;
        }

        // NEW: Broadcast attendance update via WebSocket
        broadcast(new \App\Events\AttendanceUpdated(
            $session->id,
            $user->id,
            [
                'is_currently_in_meeting' => true,
                'duration_minutes' => $attendance->getCurrentSessionDuration(),
                'join_count' => $attendance->join_count,
                'status' => 'joined',
            ]
        ))->toOthers();

        // ... rest of method ...
    }
}
```

**Similarly for `handleUserLeave()`** (ADD after line 83)
```php
public function handleUserLeave(MeetingCapable $session, User $user): bool
{
    try {
        // ... existing leave logic ...

        $leaveSuccess = $attendance->recordLeave();

        if (!$leaveSuccess) {
            return false;
        }

        // NEW: Broadcast attendance update via WebSocket
        broadcast(new \App\Events\AttendanceUpdated(
            $session->id,
            $user->id,
            [
                'is_currently_in_meeting' => false,
                'duration_minutes' => $attendance->total_duration_minutes,
                'join_count' => $attendance->join_count,
                'leave_count' => $attendance->leave_count,
                'status' => 'left',
            ]
        ))->toOthers();

        // ... rest of method ...
    }
}
```

##### **Step 3: Listen for Events in Frontend**

**File:** `resources/views/components/meetings/livekit-interface.blade.php`

Replace the polling code with Echo listener:

```javascript
// BEFORE (POLLING):
startPeriodicUpdates() {
    this.updateInterval = setInterval(() => {
        this.loadCurrentStatus();
    }, 30000); // Poll every 30 seconds
}

// AFTER (WEBSOCKET):
startRealtimeUpdates() {
    console.log('🔔 Starting real-time attendance updates via WebSocket');

    // Listen for attendance updates on this session's channel
    window.Echo.channel(`session.${this.sessionId}`)
        .listen('.attendance.updated', (event) => {
            console.log('📡 Attendance update received:', event);

            // Only update UI if this is for current user
            if (event.user_id === {{ Auth::id() }}) {
                this.updateAttendanceUI(event.attendance);
            }
        });

    // Load initial status once
    this.loadCurrentStatus();
}

// Clean up on page unload
stopRealtimeUpdates() {
    window.Echo.leaveChannel(`session.${this.sessionId}`);
}
```

##### **Step 4: Start Reverb Server**

**Terminal:**
```bash
# Start Reverb WebSocket server (built into Laravel 11)
php artisan reverb:start

# Or run in background
php artisan reverb:start --daemon
```

**Add to `composer dev` script** in `package.json`:
```json
"scripts": {
    "dev": "concurrently \"php artisan serve\" \"php artisan reverb:start\" \"php artisan queue:listen\" \"npm run dev\""
}
```

---

### **Enhancement #2: Heartbeat Mechanism**

#### **What It Is**
Frontend sends a "heartbeat" ping every 60 seconds to confirm the user is still actively connected.

#### **Why It's Useful**
- ✅ Detects stale connections (browser crashed, tab closed without disconnect)
- ✅ More reliable than waiting for LiveKit disconnect events
- ✅ Can estimate leave time even if webhook is missed

#### **Full Implementation**

##### **Step 1: Add Heartbeat Field to Database**

**Migration:**
```bash
php artisan make:migration add_heartbeat_to_meeting_attendances
```

**File:** `database/migrations/YYYY_MM_DD_add_heartbeat_to_meeting_attendances.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_leave_time');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropColumn('last_heartbeat_at');
        });
    }
};
```

Run migration:
```bash
php artisan migrate
```

##### **Step 2: Add Heartbeat Method to Model**

**File:** `app/Models/MeetingAttendance.php` (ADD after line 371)
```php
/**
 * Update heartbeat timestamp
 */
public function updateHeartbeat(): void
{
    $this->update(['last_heartbeat_at' => now()]);

    Log::debug('Heartbeat updated', [
        'session_id' => $this->session_id,
        'user_id' => $this->user_id,
    ]);
}

/**
 * Check if heartbeat is stale (no heartbeat for 5+ minutes)
 */
public function hasStaleHeartbeat(): bool
{
    if (!$this->last_heartbeat_at) {
        return false; // No heartbeat data yet
    }

    $minutesSinceHeartbeat = $this->last_heartbeat_at->diffInMinutes(now());

    return $minutesSinceHeartbeat > 5;
}
```

##### **Step 3: Update `isCurrentlyInMeeting()` to Check Heartbeat**

**File:** `app/Models/MeetingAttendance.php` (MODIFY method at line 299)
```php
public function isCurrentlyInMeeting(): bool
{
    $cycles = $this->join_leave_cycles ?? [];
    $lastCycle = end($cycles);

    $hasOpenCycle = $lastCycle && isset($lastCycle['joined_at']) && !isset($lastCycle['left_at']);

    if (!$hasOpenCycle) {
        return false;
    }

    // NEW: Check heartbeat to detect stale connections
    if ($this->hasStaleHeartbeat()) {
        Log::info('User connection appears stale - no recent heartbeat', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'last_heartbeat' => $this->last_heartbeat_at,
        ]);

        // Auto-close the stale cycle
        $this->autoCloseStaleCycles();

        return false;
    }

    // ... rest of existing logic ...
    return $hasOpenCycle;
}
```

##### **Step 4: Create Heartbeat API Endpoint**

**File:** `routes/web.php` (ADD to meetings API group)
```php
Route::prefix('meetings')->middleware(['auth'])->group(function () {
    // ... existing routes ...

    // NEW: Heartbeat endpoint
    Route::post('attendance/heartbeat', [\App\Http\Controllers\MeetingAttendanceController::class, 'heartbeat'])
        ->name('api.meetings.attendance.heartbeat');
});
```

**File:** `app/Http/Controllers/MeetingAttendanceController.php` (ADD new method)
```php
/**
 * Update heartbeat for user's attendance
 */
public function heartbeat(Request $request): JsonResponse
{
    try {
        $request->validate([
            'session_id' => 'required|integer',
            'session_type' => 'required|in:quran,academic',
        ]);

        $sessionType = $request->input('session_type');
        $sessionId = $request->input('session_id');
        $session = $this->getSessionByType($sessionType, $sessionId);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $user = Auth::user();

        // Find attendance record
        $attendance = $session->meetingAttendances()->where('user_id', $user->id)->first();

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'No attendance record'], 404);
        }

        // Only update heartbeat if user is currently in meeting
        if ($attendance->isCurrentlyInMeeting()) {
            $attendance->updateHeartbeat();

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat updated',
                'last_heartbeat' => $attendance->last_heartbeat_at->toISOString(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not currently in meeting',
        ], 400);

    } catch (\Exception $e) {
        Log::error('Failed to update heartbeat', [
            'error' => $e->getMessage(),
            'user_id' => Auth::id(),
        ]);

        return response()->json(['success' => false, 'message' => 'Error'], 500);
    }
}
```

##### **Step 5: Send Heartbeat from Frontend**

**File:** `resources/views/components/meetings/livekit-interface.blade.php`

Add heartbeat sending:
```javascript
class AutoAttendanceTracker {
    constructor() {
        // ... existing constructor ...
        this.heartbeatInterval = null;
    }

    /**
     * Start sending heartbeat pings
     */
    startHeartbeat() {
        console.log('💓 Starting heartbeat pings');

        // Send heartbeat every 60 seconds
        this.heartbeatInterval = setInterval(async () => {
            if (this.isTracking) {
                try {
                    const response = await fetch('/api/meetings/attendance/heartbeat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            session_type: '{{ $sessionType }}'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        console.log('💓 Heartbeat sent successfully');
                    }
                } catch (error) {
                    console.warn('⚠️ Failed to send heartbeat:', error);
                }
            }
        }, 60000); // Every 60 seconds
    }

    /**
     * Stop heartbeat pings
     */
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
            console.log('💓 Heartbeat stopped');
        }
    }

    // Modify hookIntoMeeting to start heartbeat
    hookIntoMeeting(meeting) {
        // ... existing hook logic ...

        room.on('connected', () => {
            console.log('📡 Connected to room');
            this.isTracking = true;
            this.startHeartbeat(); // NEW: Start heartbeat when connected
        });

        room.on('disconnected', () => {
            console.log('📡 Disconnected from room');
            this.isTracking = false;
            this.stopHeartbeat(); // NEW: Stop heartbeat when disconnected
        });
    }
}
```

---

### **Enhancement #3: Improve LiveKit Webhook Reliability**

#### **What It Is**
Add retry logic, validation, and failure handling for LiveKit webhooks.

#### **Why It's Important**
- ✅ Network issues won't lose attendance data
- ✅ Validates webhooks are authentic
- ✅ Retries failed operations

#### **Full Implementation**

##### **Step 1: Add Webhook Signature Validation**

**File:** `app/Http/Controllers/LiveKitWebhookController.php` (ADD at top of `handleWebhook` method)
```php
public function handleWebhook(Request $request): Response
{
    try {
        // NEW: Validate webhook signature
        if (!$this->validateWebhookSignature($request)) {
            Log::warning('Invalid LiveKit webhook signature');
            return response('Unauthorized', 401);
        }

        $event = $request->input('event');
        // ... rest of method ...
    }
}

/**
 * Validate webhook signature from LiveKit
 */
private function validateWebhookSignature(Request $request): bool
{
    $webhookSecret = config('livekit.webhook.secret');

    if (!$webhookSecret) {
        Log::warning('LiveKit webhook secret not configured');
        return true; // Allow if not configured (dev mode)
    }

    $signature = $request->header('LiveKit-Signature');
    $body = $request->getContent();

    $expectedSignature = hash_hmac('sha256', $body, $webhookSecret);

    return hash_equals($expectedSignature, $signature);
}
```

##### **Step 2: Add Retry Queue Job**

**Create Job:**
```bash
php artisan make:job RetryAttendanceOperation
```

**File:** `app/Jobs/RetryAttendanceOperation.php`
```php
<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MeetingAttendanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryAttendanceOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // Retry after 1 minute

    public function __construct(
        public int $sessionId,
        public int $userId,
        public string $operation, // 'join' or 'leave'
    ) {}

    public function handle(MeetingAttendanceService $service): void
    {
        Log::info('Retrying attendance operation', [
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'operation' => $this->operation,
            'attempt' => $this->attempts(),
        ]);

        $session = \App\Models\QuranSession::find($this->sessionId);
        $user = User::find($this->userId);

        if (!$session || !$user) {
            Log::error('Session or user not found for retry');
            return;
        }

        try {
            if ($this->operation === 'join') {
                $service->handleUserJoin($session, $user);
            } elseif ($this->operation === 'leave') {
                $service->handleUserLeave($session, $user);
            }

            Log::info('Attendance operation retry successful');
        } catch (\Exception $e) {
            Log::error('Attendance operation retry failed', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e; // Re-throw to trigger another retry
        }
    }
}
```

##### **Step 3: Use Retry Job in Webhook Handler**

**File:** `app/Http/Controllers/LiveKitWebhookController.php` (MODIFY handleParticipantJoined/Left)
```php
private function handleParticipantJoined(array $data): void
{
    // ... existing code to get session and user ...

    try {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->attendanceService->handleUserJoin($session, $user);

                Log::info('Participant joined - attendance recorded', [
                    'session_id' => $session->id,
                    'user_id' => $userId,
                ]);
            }
        }
    } catch (\Exception $e) {
        Log::error('Failed to handle participant joined event', [
            'session_id' => $session->id ?? 'unknown',
            'user_id' => $userId ?? 'unknown',
            'error' => $e->getMessage(),
        ]);

        // NEW: Queue retry job
        if ($userId && $session) {
            \App\Jobs\RetryAttendanceOperation::dispatch(
                $session->id,
                $userId,
                'join'
            )->delay(now()->addMinutes(1));

            Log::info('Queued attendance join retry job');
        }
    }
}

// Similar changes for handleParticipantLeft
```

---

## 📊 **SUMMARY: What to Implement**

### **Phase 1: IMMEDIATE FIX (Required)**
1. ✅ Comment out all `recordJoin()` calls in frontend
2. ✅ Comment out all `recordLeave()` calls in frontend
3. ✅ Keep polling for **display only**
4. ✅ Verify LiveKit webhooks are working
5. ✅ Test with actual meeting join/leave

**Result:** Attendance only tracks time in ACTUAL LiveKit meeting

---

### **Phase 2: WEBSOCKET ENHANCEMENT (Recommended - 2-3 hours)**
1. Create `AttendanceUpdated` event
2. Broadcast event when attendance changes
3. Replace frontend polling with Echo listeners
4. Start Reverb server

**Result:** 95% fewer API calls, instant real-time updates

---

### **Phase 3: HEARTBEAT SYSTEM (Optional - 2 hours)**
1. Add `last_heartbeat_at` column to database
2. Create heartbeat API endpoint
3. Send heartbeat from frontend every 60 seconds
4. Check heartbeat in `isCurrentlyInMeeting()`

**Result:** Detect stale connections, more reliable tracking

---

### **Phase 4: WEBHOOK RELIABILITY (Optional - 1 hour)**
1. Add webhook signature validation
2. Create retry queue job
3. Use retry job for failed operations

**Result:** Zero data loss even with network issues

---

## 🎯 **Expected Outcomes After Implementation**

### Before Fixes
- ❌ Attendance tracked from when user loads page
- ❌ Minutes increment even without joining meeting
- ❌ Can close video but still get attendance credit
- ❌ 120 API calls per hour per user
- ❌ Data can be lost if webhooks fail

### After Fixes
- ✅ Attendance ONLY tracked in actual LiveKit meeting
- ✅ Minutes increment ONLY when in video call
- ✅ Closing video = attendance stops immediately
- ✅ 0-5 API calls per hour per user (with WebSocket)
- ✅ Retry mechanism prevents data loss

---

## 🔧 **Testing Checklist**

After implementing Phase 1 (immediate fix):

- [ ] Join meeting as student
- [ ] Verify attendance starts ONLY after LiveKit connects
- [ ] Leave meeting
- [ ] Verify attendance stops immediately
- [ ] Reload page while NOT in meeting
- [ ] Verify no attendance is recorded
- [ ] Stay in meeting for 30 minutes
- [ ] Verify duration shows exactly 30 minutes (not more)

---

**Questions? Issues? Check logs:**
```bash
# Monitor LiveKit webhooks
php artisan pail --filter="LiveKit webhook"

# Monitor attendance operations
php artisan pail --filter="attendance"

# Check for errors
php artisan pail --filter="error"
```
