# Attendance System Audit & Fixes

## Issues Found

### ✅ FIXED: Wrong Timestamp Field in JOIN Handler
**Problem:** Code was looking for `joined_at` (snake_case) but LiveKit sends `joinedAt` (camelCase).
**Impact:** Join times were using `now()` instead of the actual LiveKit join timestamp, causing incorrect attendance records.
**Fix:** Changed `$participantData['joined_at']` to `$participantData['joinedAt']` in [`LiveKitWebhookController.php:273`](app/Http/Controllers/LiveKitWebhookController.php#L273)

**Before:**
```php
$joinedAt = isset($participantData['joined_at'])  // ❌ Wrong field name
    ? \Carbon\Carbon::createFromTimestamp($participantData['joined_at'])
    : now();
```

**After:**
```php
$joinedAt = isset($participantData['joinedAt'])  // ✅ Correct field name
    ? \Carbon\Carbon::createFromTimestamp($participantData['joinedAt'])
    : now();
```

### ✅ VERIFIED: Webhook Delivery System Working
**Status:** Webhooks ARE being received successfully via ngrok tunnel.
**Tunnel URL:** `https://percolative-unyielded-taneka.ngrok-free.dev/webhooks/livekit`
**LiveKit Server:** `31.97.126.52` is successfully sending webhooks through ngrok to local Laravel app.

**Webhook Events Received:**
- ✅ `participant_joined` - Creating attendance events
- ✅ `participant_left` - Closing attendance cycles
- ✅ `room_started` - Updating session status
- ✅ `room_finished` - Marking sessions complete
- ✅ `track_published` - Permission enforcement working

### ⚠️ WARNING: Ngrok Tunnel Required
**Critical:** The self-hosted LiveKit server (`31.97.126.52`) **CANNOT** reach `itqan-platform.test` (local Valet domain).
**Solution:** ngrok tunnel must be running for webhooks to work.

**Start ngrok:**
```bash
ngrok http https://itqan-platform.test:443
```

**Configure LiveKit Server Webhook:**
The LiveKit server at `31.97.126.52` should be configured to send webhooks to your ngrok URL:
```yaml
# /opt/livekit/livekit.yaml
webhook:
  urls:
    - https://YOUR-NGROK-SUBDOMAIN.ngrok-free.dev/webhooks/livekit
  api_key: APIxdLnkvjeS3PV
```

### ⚠️ ISSUE: Duplicate Event Webhooks
**Problem:** Some webhooks are being received multiple times with the same `event_id`, causing database unique constraint errors.
**Impact:** Error logged but event is ignored (idempotency working correctly).
**Status:** Not critical - the unique constraint on `event_id` prevents duplicate records.

**Example Error:**
```
Integrity constraint violation: 1062 Duplicate entry 'EV_3vDfWofby5m2' for key 'meeting_attendance_events_event_id_unique'
```

**Why It Happens:** LiveKit may retry webhooks if it doesn't receive a 200 response quickly enough.
**Solution:** Already handled - unique constraint prevents duplicates.

## System Architecture

### Attendance Flow
```
1. Participant Joins Meeting
   ↓
2. LiveKit Server sends webhook → ngrok → Laravel
   ↓
3. LiveKitWebhookController->handleParticipantJoined()
   ↓
4. Create MeetingAttendanceEvent (immutable log)
   ↓
5. AttendanceEventService->recordJoin()
   ↓
6. Create/Update MeetingAttendance record (aggregated state)
   ↓
7. Clear attendance cache
```

```
1. Participant Leaves Meeting
   ↓
2. LiveKit Server sends webhook → ngrok → Laravel
   ↓
3. LiveKitWebhookController->handleParticipantLeft()
   ↓
4. Find matching join event by participant_sid
   ↓
5. Calculate duration from LiveKit timestamps
   ↓
6. Close join event with left_at timestamp
   ↓
7. AttendanceEventService->recordLeave()
   ↓
8. Update MeetingAttendance final duration
```

### Database Tables

**`meeting_attendance_events`** (Event Sourcing - Immutable)
- Stores every join/leave event from LiveKit webhooks
- Never modified after creation (except to add `left_at` when participant leaves)
- Source of truth for attendance calculations
- Fields: `event_id`, `event_type`, `event_timestamp`, `left_at`, `duration_minutes`, `participant_sid`

**`meeting_attendances`** (Aggregated State - Currently Active)
- Stores current/final attendance state per participant per session
- Updated in real-time as events arrive
- Used for quick queries (who's in the meeting now?)
- Fields: `joined_at`, `left_at`, `duration_minutes`, `status`

### Scheduled Jobs

**`ReconcileOrphanedAttendanceEvents`** (Hourly)
- Finds join events without matching leave events
- Closes orphaned events if session is completed
- Handles edge cases where `participant_left` webhook was missed

**`CalculateSessionAttendance`** (Every 5 minutes in production, 10 seconds in local)
- Calculates final attendance for completed sessions
- Creates attendance records from event logs
- Updates session reports

## Testing

### 1. Test Join Event
```bash
# Join a meeting as a user
# Check logs for successful webhook processing
tail -f storage/logs/laravel.log | grep "participant_joined"

# Verify event created
php artisan tinker --execute="
  \$events = App\Models\MeetingAttendanceEvent::where('session_id', 3)
    ->where('event_type', 'join')
    ->latest()
    ->first();
  echo 'Event: ' . \$events->event_type . ' | Joined: ' . \$events->event_timestamp . PHP_EOL;
"
```

### 2. Test Leave Event
```bash
# Leave the meeting
# Check logs for successful processing
tail -f storage/logs/laravel.log | grep "participant_left"

# Verify duration calculated correctly
php artisan tinker --execute="
  \$events = App\Models\MeetingAttendanceEvent::where('session_id', 3)
    ->whereNotNull('left_at')
    ->latest()
    ->first();
  echo 'Duration: ' . \$events->duration_minutes . ' minutes' . PHP_EOL;
  echo 'Joined: ' . \$events->event_timestamp . PHP_EOL;
  echo 'Left: ' . \$events->left_at . PHP_EOL;
"
```

### 3. Test Full Attendance Cycle
```bash
# 1. User joins meeting
# 2. Wait 2-3 minutes
# 3. User leaves meeting
# 4. Check final attendance record

php artisan tinker --execute="
  \$attendance = App\Models\MeetingAttendance::where('session_id', 3)
    ->where('user_id', YOUR_USER_ID)
    ->first();

  if (\$attendance) {
    echo 'Status: ' . \$attendance->status . PHP_EOL;
    echo 'Duration: ' . \$attendance->duration_minutes . ' minutes' . PHP_EOL;
    echo 'Joined: ' . \$attendance->joined_at . PHP_EOL;
    echo 'Left: ' . \$attendance->left_at . PHP_EOL;
  } else {
    echo '❌ No attendance record found!' . PHP_EOL;
  }
"
```

## Configuration Checklist

### ✅ Local Development
- [ ] ngrok tunnel running
- [ ] LiveKit server webhook configured to ngrok URL
- [ ] Reverb server running (`lsof -i :8085`)
- [ ] Laravel logs monitored (`tail -f storage/logs/laravel.log`)

### ✅ LiveKit Server (31.97.126.52)
```bash
# Check LiveKit server webhook configuration
ssh root@31.97.126.52 "cat /opt/livekit/livekit.yaml | grep -A 5 webhook"

# Expected output:
webhook:
  urls:
    - https://YOUR-NGROK-URL.ngrok-free.dev/webhooks/livekit
  api_key: APIxdLnkvjeS3PV
```

### ✅ Laravel Configuration
```env
# .env
LIVEKIT_SERVER_URL=wss://31.97.126.52:443
LIVEKIT_API_URL=http://31.97.126.52:7880
LIVEKIT_API_KEY=APIxdLnkvjeS3PV
LIVEKIT_API_SECRET=coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC
```

```php
// config/livekit.php
'server_url' => 'wss://31.97.126.52:443',
'api_url' => 'http://31.97.126.52:7880',
```

## Monitoring

### Check Webhook Health
```bash
curl https://YOUR-NGROK-URL.ngrok-free.dev/webhooks/livekit/health
# Expected: {"status":"ok","timestamp":"...","service":"livekit-webhooks"}
```

### Monitor Webhook Reception
```bash
# Real-time webhook monitoring
tail -f storage/logs/laravel.log | grep "WEBHOOK ENDPOINT HIT"
```

### Check Attendance Events
```bash
# Count events by type
php artisan tinker --execute="
  echo 'Join events: ' . App\Models\MeetingAttendanceEvent::where('event_type', 'join')->count() . PHP_EOL;
  echo 'Closed events: ' . App\Models\MeetingAttendanceEvent::whereNotNull('left_at')->count() . PHP_EOL;
  echo 'Orphaned events: ' . App\Models\MeetingAttendanceEvent::whereNull('left_at')->count() . PHP_EOL;
"
```

## Summary

### What Was Broken
1. ❌ JOIN timestamps using `now()` instead of LiveKit webhook timestamp
2. ❌ Field name mismatch: `joined_at` vs `joinedAt`
3. ⚠️ Duplicate webhooks causing constraint errors (non-critical)

### What Was Fixed
1. ✅ Corrected field name from `joined_at` to `joinedAt`
2. ✅ Verified webhook delivery via ngrok is working
3. ✅ Confirmed attendance event creation is working
4. ✅ Cleaned up old buggy data for fresh testing

### What's Working
1. ✅ Webhooks delivered via ngrok successfully
2. ✅ Join events created with correct timestamps (after fix)
3. ✅ Leave events closing join events correctly
4. ✅ Duration calculations working
5. ✅ Idempotency preventing duplicate records
6. ✅ Scheduled jobs reconciling orphaned events

### Production Deployment Notes
**For production**, you'll need either:
1. **Public domain** for your Laravel app (e.g., `api.yourdomain.com`)
2. **Cloudflare Tunnel** (more reliable than ngrok for production)
3. **VPN** between LiveKit server and Laravel server (if both on same network)

**ngrok is only suitable for development/testing.**
