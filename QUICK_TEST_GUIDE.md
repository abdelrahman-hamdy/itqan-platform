# 🚀 Quick Test Guide - Attendance System

## Current Status: ✅ WORKING

The attendance tracking system is now fully functional!

## Quick Test Commands

### 1. Test Complete Attendance Cycle (Recommended)
```bash
./test-complete-attendance-cycle.sh && sleep 2 && php artisan attendance:debug 121
```

**What it does**:
- Sends JOIN event for session 121
- Waits 2 seconds
- Sends LEAVE event (5 minutes duration)
- Shows results

**Expected output**:
```
✓ JOIN event with 5min duration
Attendance record with ~5min total duration
```

### 2. Test Short Session (2 minutes)
```bash
./test-short-session.sh && sleep 1 && php artisan attendance:debug 121
```

**What it does**:
- Simulates a 2-minute session
- Shows results immediately

### 3. Watch Real-time Attendance
```bash
php artisan attendance:debug 121 --watch
```

**What it does**:
- Refreshes every 5 seconds
- Shows webhook events and attendance records
- Press Ctrl+C to stop

### 4. Clear Test Data
```bash
php artisan attendance:clean --before=2025-11-15 --force
```

**What it does**:
- Removes all test attendance data
- Prepares for fresh testing

## Production Testing with Real LiveKit

### 1. Ensure ngrok is Running
```bash
# Check if ngrok is active
curl https://percolative-unyielded-taneka.ngrok-free.dev/webhooks/livekit

# Should return: "OK" or method not allowed (that's fine)
```

### 2. Join a Real Session
1. Open browser: `https://itqan-academy.itqan-platform.test/teacher-panel/calendar`
2. Join session 121 (or any active session)
3. Wait 2-3 minutes
4. Leave the session

### 3. Monitor Attendance
```bash
# In another terminal, watch for webhooks
php artisan attendance:debug 121 --watch
```

**Expected behavior**:
- JOIN event appears when you join
- Duration shows "0min" while in meeting
- LEAVE event appears when you leave
- Duration updates to actual time spent

## Troubleshooting Quick Checks

### Check 1: Webhooks Reaching Server?
```bash
tail -f storage/logs/laravel.log | grep "WEBHOOK ENDPOINT HIT"
```
- Should see logs when webhook is received
- If empty, check ngrok configuration

### Check 2: Events Being Created?
```bash
php artisan tinker --execute="echo 'Events: ' . App\Models\MeetingAttendanceEvent::count();"
```
- Should show number > 0 after testing
- If 0, webhooks not processing correctly

### Check 3: Participant Identity Format?
```bash
php artisan tinker --execute="echo App\Models\MeetingAttendanceEvent::latest()->first()->participant_identity ?? 'None';"
```
- Should show format: `{userId}_{firstName}_{lastName}`
- Example: `1_Ameer_Maher`

### Check 4: Room Names Match?
```bash
php artisan tinker --execute="echo App\Models\QuranSession::find(121)->meeting_room_name ?? 'Not found';"
```
- Should show: `itqan-academy-quran-session-121`
- This must match webhook room name

## Common Issues and Solutions

### Issue: Duration always 0min
**Solution**:
- Ensure LEAVE event was sent
- Check participant SID matches between JOIN and LEAVE
- Run: `./test-complete-attendance-cycle.sh`

### Issue: No webhook events appearing
**Solution**:
```bash
# Restart ngrok
pkill ngrok
ngrok http https://itqan-platform.test --host-header=itqan-platform.test

# Update webhook URL in LiveKit dashboard with new ngrok URL
```

### Issue: "Session NOT found with room name"
**Solution**:
- Check room name format in webhook
- Should be: `itqan-academy-{sessionType}-session-{id}`
- Use: `./test-livekit-webhook-direct.sh` (already has correct format)

### Issue: "Could not extract user ID from participant identity"
**Solution**:
- Check identity format: must be `{userId}_{firstName}_{lastName}`
- Example: `1_Ameer_Maher`, NOT `itqan_1`
- Test scripts now use correct format

## Success Indicators

✅ **Webhook events table showing recent events**
```bash
php artisan attendance:debug 121
# Should show JOIN/LEAVE events with timestamps
```

✅ **Duration being calculated**
```bash
php artisan attendance:debug 121
# Duration column should show actual minutes, not 0min
```

✅ **Last Leave time showing after leaving**
```bash
php artisan attendance:debug 121
# Should show timestamp, not "In meeting"
```

✅ **Multiple cycles tracked**
```bash
# Join and leave multiple times
php artisan attendance:debug 121
# Cycles count should increase, duration should accumulate
```

## Development vs Production

### Development (Current)
- Queue: `sync` (immediate processing)
- Calculation job: Every 10 seconds
- Webhook source: ngrok tunnel
- Testing: Simulated webhooks

### Production (Next Step)
- Queue: `database` (queued processing)
- Calculation job: Every 5 minutes
- Webhook source: Direct from LiveKit Cloud
- Testing: Real user sessions

## Next Steps for Production

1. **Configure LiveKit Dashboard**
   - URL: `https://yourdomain.com/webhooks/livekit`
   - Events: `participant_joined`, `participant_left`

2. **Update .env**
   ```bash
   QUEUE_CONNECTION=database
   APP_ENV=production
   ```

3. **Restart Services**
   ```bash
   php artisan queue:restart
   php artisan config:clear
   ```

4. **Monitor First Real Session**
   ```bash
   php artisan attendance:debug --watch
   ```

---

**System Status**: ✅ Working perfectly in development
**Ready for**: Production deployment
**Test Coverage**: JOIN/LEAVE cycles, duration calculation, multi-cycle tracking
