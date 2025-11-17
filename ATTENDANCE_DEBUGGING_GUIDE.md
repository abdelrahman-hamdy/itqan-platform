# Attendance System Debugging Guide

**New Webhook-Based System - No Client-Side Tracking**

---

## Overview

The attendance system is now **100% webhook-based**:
- ✅ LiveKit sends webhooks when users join/leave
- ✅ Backend stores events immediately
- ✅ Attendance calculated 5 minutes after session ends
- ❌ **NO client-side tracking**
- ❌ **NO polling**
- ❌ **NO real-time API calls**

---

## Debugging Tools

### 1. Real-Time Webhook Monitoring (Terminal)

Watch webhooks as they arrive in real-time:

```bash
# Watch all webhook activity (refreshes every 5 seconds)
php artisan attendance:debug --watch

# Watch specific session
php artisan attendance:debug 123 --watch

# Show last 20 events (no auto-refresh)
php artisan attendance:debug --events=20
```

**Output Example:**
```
=== LiveKit Webhook Activity (Last 5 Minutes) ===

Time     | Event         | Session | User            | Participant SID | Duration
---------|---------------|---------|-----------------|-----------------|----------
14:32:15 | ✓ JOIN        | #456    | Ahmed Mohamed   | PA_abc123...    | -
14:33:42 | ✗ LEAVE       | #456    | Ahmed Mohamed   | PA_abc123...    | 12min

=== Current Attendance Records (Uncalculated) ===

Session | User            | First Join | Last Leave  | Cycles | Duration
--------|-----------------|------------|-------------|--------|----------
#456    | Ahmed Mohamed   | 14:32:15   | In meeting  | 1      | 12min
```

---

### 2. Application Logs (Laravel Pail)

View detailed webhook logs in real-time:

```bash
php artisan pail
```

**Look for these log messages:**

**✅ JOIN Event:**
```
✅ [WEBHOOK] JOIN event processed
  webhook_id: "evt_abc123..."
  event_db_id: 789
  session_id: 456
  session_name: "دورة القرآن الكريم"
  user_id: 12
  user_name: "Ahmed Mohamed"
  participant_sid: "PA_abc123..."
  joined_at: "2025-11-14T14:32:15.000Z"
  room_name: "session-456"
  participant_count: 3
```

**✅ LEAVE Event:**
```
✅ [WEBHOOK] LEAVE event processed
  webhook_id: "evt_def456..."
  event_db_id: 789
  session_id: 456
  session_name: "دورة القرآن الكريم"
  user_id: 12
  user_name: "Ahmed Mohamed"
  participant_sid: "PA_abc123..."
  joined_at: "2025-11-14T14:32:15.000Z"
  left_at: "2025-11-14T14:44:30.000Z"
  duration_minutes: 12
  room_name: "session-456"
  remaining_participants: 2
```

---

### 3. Database Inspection

#### Check Recent Webhook Events:
```sql
SELECT
    id,
    event_type,
    event_timestamp,
    session_id,
    user_id,
    participant_sid,
    duration_minutes,
    created_at
FROM meeting_attendance_events
ORDER BY event_timestamp DESC
LIMIT 20;
```

#### Check Uncalculated Attendance:
```sql
SELECT
    session_id,
    user_id,
    first_join_time,
    last_leave_time,
    total_duration_minutes,
    join_count,
    leave_count,
    is_calculated
FROM meeting_attendances
WHERE is_calculated = FALSE
ORDER BY updated_at DESC;
```

#### Check Calculated Attendance:
```sql
SELECT
    session_id,
    user_id,
    attendance_status,
    attendance_percentage,
    total_duration_minutes,
    attendance_calculated_at
FROM meeting_attendances
WHERE is_calculated = TRUE
ORDER BY attendance_calculated_at DESC
LIMIT 20;
```

---

## How the System Works

### Flow Diagram

```
User Joins Meeting
    ↓
LiveKit Server detects participant_joined
    ↓
LiveKit sends webhook to: POST /webhooks/livekit
    ↓
LiveKitWebhookController->handleParticipantJoined()
    ↓
1. Create MeetingAttendanceEvent (immutable log)
2. Call AttendanceEventService->recordJoin()
    ↓
MeetingAttendance updated:
  - first_join_time set (if first time)
  - join event added to join_leave_cycles
  - is_calculated = false
    ↓
Log: "✅ [WEBHOOK] JOIN event processed"

═══════════════════════════════

User Leaves Meeting
    ↓
LiveKit Server detects participant_left
    ↓
LiveKit sends webhook to: POST /webhooks/livekit
    ↓
LiveKitWebhookController->handleParticipantLeft()
    ↓
1. Close MeetingAttendanceEvent (set left_at, duration)
2. Call AttendanceEventService->recordLeave()
    ↓
MeetingAttendance updated:
  - leave event added to join_leave_cycles
  - last_leave_time set
  - total_duration_minutes recalculated
  - is_calculated = false
    ↓
Log: "✅ [WEBHOOK] LEAVE event processed"

═══════════════════════════════

Session Ends (scheduled_end_at reached)
    ↓
Wait 5 minutes (grace period for late webhooks)
    ↓
CalculateSessionAttendance job runs (every 5 min)
    ↓
For each uncalculated attendance:
  1. Sum all join/leave cycles
  2. Determine status (present/late/partial/absent)
  3. Calculate percentage
  4. Set is_calculated = true
  5. Sync to BaseSessionReport
    ↓
Final attendance visible to users
```

---

## Testing Webhooks

### Manual Webhook Test (Development Only)

**Test JOIN webhook:**
```bash
curl -X POST http://itqan-platform.test/webhooks/livekit \
  -H "Content-Type: application/json" \
  -d '{
    "event": "participant_joined",
    "id": "test_join_' $(date +%s) '",
    "room": {
      "name": "session-123",
      "num_participants": 1
    },
    "participant": {
      "sid": "PA_test_' $(date +%s) '",
      "identity": "user-456",
      "name": "Test User",
      "joined_at": ' $(date +%s) '
    }
  }'
```

**Check logs immediately:**
```bash
php artisan pail | grep WEBHOOK
```

---

## Common Issues & Solutions

### Issue 1: No Webhooks Received

**Symptoms:**
- No logs with `[WEBHOOK]` prefix
- `meeting_attendance_events` table empty
- `php artisan attendance:debug` shows no activity

**Solutions:**
1. Check LiveKit webhook configuration in `livekit.yaml`:
   ```yaml
   webhook:
     api_key: your-webhook-secret-key
     urls:
       - https://itqan-platform.test/webhooks/livekit
   ```

2. Verify webhook route is accessible:
   ```bash
   curl -I https://itqan-platform.test/webhooks/livekit
   # Should return 405 (POST required) or 401 (missing auth)
   ```

3. Check LiveKit server logs:
   ```bash
   docker logs livekit-server | grep webhook
   ```

---

### Issue 2: Duplicate Webhooks

**Symptoms:**
- Log shows: "Duplicate join webhook ignored"
- Multiple events with same `event_id`

**Solution:**
✅ This is **NORMAL** and handled automatically. The system uses `event_id` (webhook UUID) to prevent duplicates. No action needed.

---

### Issue 3: Leave Webhook Arrives Before Join

**Symptoms:**
- Log shows: "No matching join event found for leave"
- Retry job dispatched

**Solution:**
✅ This is **NORMAL** and handled automatically. The system retries after 5 seconds. No action needed.

---

### Issue 4: Attendance Not Calculated After Session

**Symptoms:**
- Session ended > 5 minutes ago
- `is_calculated` still FALSE
- No attendance percentage shown

**Solutions:**

1. Check if calculation job is running:
   ```bash
   php artisan schedule:work
   # Or
   composer dev  # Includes scheduler
   ```

2. Manually trigger calculation:
   ```bash
   php artisan attendance:calculate 123
   ```

3. Check calculation job logs:
   ```bash
   php artisan pail | grep "CalculateSessionAttendance"
   ```

---

### Issue 5: User Still Shows "In Meeting" After Leaving

**Symptoms:**
- `last_leave_time` is NULL
- User left but no LEAVE webhook received

**Solutions:**

1. Check if user actually left LiveKit room:
   ```bash
   # List active rooms
   docker exec livekit-server livekit-cli list-rooms
   ```

2. Wait for reconciliation job (runs hourly):
   ```bash
   # Or manually run:
   php artisan schedule:run
   ```

3. Check for missed webhooks in LiveKit logs:
   ```bash
   docker logs livekit-server | grep participant_left
   ```

---

## Performance Monitoring

### Webhook Processing Time

Monitor how fast webhooks are processed:

```bash
php artisan pail | grep "WEBHOOK\|Failed to handle"
```

**Healthy:**
- JOIN events logged within 100ms of webhook receipt
- LEAVE events logged within 100ms
- No "Failed to handle" errors

**Problematic:**
- Delays > 500ms
- Frequent "Failed to handle" errors
- Missing events

---

### Calculation Job Performance

Monitor post-meeting calculation:

```bash
php artisan pail | grep "CalculateSessionAttendance\|Post-meeting"
```

**Healthy:**
- Job completes < 30 seconds for 100 sessions
- "processed" count increases
- "failed" count is 0

**Problematic:**
- Job timeout
- High "failed" count
- "processed" count stuck at 0

---

## Development vs Production

### Development (Local)

**What's Active:**
- ✅ Enhanced webhook logging
- ✅ Debug command available
- ✅ Laravel Pail for real-time logs
- ✅ Manual webhook testing allowed

**Webhook URL:**
```
https://itqan-platform.test/webhooks/livekit
```

---

### Production

**What's Active:**
- ✅ Standard webhook logging (less verbose)
- ✅ Debug command available (admin only)
- ✅ Automated monitoring recommended
- ❌ Manual webhook testing disabled

**Webhook URL:**
```
https://your-production-domain.com/webhooks/livekit
```

**Important:**
- Set up webhook monitoring/alerts
- Monitor calculation job success rate
- Set up database backup before migration

---

## Quick Reference

### Commands
```bash
# Watch webhook activity
php artisan attendance:debug --watch

# Calculate attendance manually
php artisan attendance:calculate {session-id}

# View logs
php artisan pail

# Run scheduler (includes calculation job)
php artisan schedule:work
```

### Log Patterns to Watch For
```
✅ [WEBHOOK] JOIN event processed      # Good - join received
✅ [WEBHOOK] LEAVE event processed     # Good - leave received
❌ Failed to handle participant joined # Bad - webhook failed
⚠️  No matching join event found      # Warning - will retry
✅ Post-meeting attendance calculation # Good - calculation running
```

### Database Tables
```
meeting_attendance_events  # Immutable webhook log
meeting_attendances        # Aggregated attendance state
```

---

## Summary

**The attendance system is now completely server-side:**

1. ✅ LiveKit webhooks are the ONLY source of attendance data
2. ✅ No client-side tracking whatsoever
3. ✅ Attendance calculated 5 minutes after session ends
4. ✅ Complete audit trail in `meeting_attendance_events`
5. ✅ Debugging available via logs and commands

**To debug, use:**
- `php artisan attendance:debug --watch` (terminal)
- `php artisan pail` (application logs)
- Database queries (direct inspection)

**NO polling, NO client-side APIs, NO real-time calculations during the meeting.**
