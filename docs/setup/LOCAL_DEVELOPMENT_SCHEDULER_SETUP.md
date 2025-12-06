# Laravel Scheduler Setup for Local Development (Valet)

## Problem Overview

The sessions and meetings system relies on Laravel's scheduled commands (cron jobs) to:
- Transition sessions from `SCHEDULED` → `READY` (10 minutes before session)
- Create LiveKit meeting rooms
- Transition sessions from `READY` → `ONGOING` (when session starts)
- Auto-complete sessions from `ONGOING` → `COMPLETED` (after duration + 5 min buffer)
- Mark absent students (individual sessions)
- Clean up expired meetings

**In production**, these commands run automatically via system cron. **In local development with Valet**, you need to manually keep the scheduler running.

## Symptoms of Scheduler Not Running

- ❌ Sessions stuck in `SCHEDULED` status even after time has passed
- ❌ Meeting links not being created ("جاري تحضير الاجتماع" forever)
- ❌ Sessions not auto-completing after they end
- ❌ Status transitions only happening when you manually run commands

## Solutions for Local Development

### Option 1: Simple Terminal Window (Recommended for Development)

**Easiest way - Just keep a terminal window open:**

```bash
cd /Users/abdelrahmanhamdy/web/itqan-platform
./run-scheduler.sh
```

This will run the scheduler every minute in your terminal. Keep this terminal window open while developing.

**Press Ctrl+C to stop when done.**

---

### Option 2: Background Process with LaunchAgent (Recommended for Always-On)

**Setup once, runs automatically even after reboot:**

#### Step 1: Copy the plist file to LaunchAgents directory

```bash
cp /Users/abdelrahmanhamdy/web/itqan-platform/com.itqan.scheduler.plist ~/Library/LaunchAgents/
```

#### Step 2: Load the agent

```bash
launchctl load ~/Library/LaunchAgents/com.itqan.scheduler.plist
```

#### Step 3: Start the agent

```bash
launchctl start com.itqan.scheduler
```

#### Managing the LaunchAgent:

**Check if it's running:**
```bash
launchctl list | grep itqan
```

**Stop the agent:**
```bash
launchctl stop com.itqan.scheduler
```

**Unload the agent (remove from startup):**
```bash
launchctl unload ~/Library/LaunchAgents/com.itqan.scheduler.plist
```

**Restart the agent:**
```bash
launchctl stop com.itqan.scheduler
launchctl start com.itqan.scheduler
```

**View logs:**
```bash
# Worker logs
tail -f /Users/abdelrahmanhamdy/web/itqan-platform/storage/logs/scheduler-worker.log

# LaunchAgent system logs
tail -f /Users/abdelrahmanhamdy/web/itqan-platform/storage/logs/scheduler-launchd.log
tail -f /Users/abdelrahmanhamdy/web/itqan-platform/storage/logs/scheduler-launchd-error.log
```

---

### Option 3: Manual Testing (Good for Debugging)

**Run scheduler once manually:**

```bash
cd /Users/abdelrahmanhamdy/web/itqan-platform
php artisan schedule:run
```

**Run specific commands:**

```bash
# Update all session statuses
php artisan sessions:update-statuses --verbose

# Manage Quran session meetings
php artisan sessions:manage-meetings

# Manage Academic session meetings
php artisan academic-sessions:manage-meetings

# Create scheduled meetings
php artisan meetings:create-scheduled

# Cleanup expired meetings
php artisan meetings:cleanup-expired
```

---

## Scheduled Commands Summary

| Command | Schedule | Purpose |
|---------|----------|---------|
| `sessions:update-statuses` | Every minute | Updates session statuses (SCHEDULED→READY→ONGOING→COMPLETED) |
| `sessions:manage-meetings` | Every minute | Manages Quran session meetings (create/update/cleanup) |
| `academic-sessions:manage-meetings` | Every minute | Manages Academic session meetings (create/update/cleanup) |
| `meetings:create-scheduled` | Every minute | Creates LiveKit meeting rooms for ready sessions |
| `meetings:cleanup-expired` | Every minute | Terminates expired meeting rooms |

**Note:** In production, these should run every 3-5 minutes. Currently set to every minute for testing.

---

## How Sessions Work (Status Flow)

### Normal Flow:

```
1. SCHEDULED (created by teacher/system)
   ↓ (10 minutes before scheduled time)
2. READY (meeting link created, preparation time)
   ↓ (scheduled time arrives or first join)
3. ONGOING (session in progress)
   ↓ (scheduled_at + duration + 5 min buffer)
4. COMPLETED (session finished, attendance recorded)
```

### Individual Session Absent Flow:

```
1. SCHEDULED
   ↓ (10 minutes before)
2. READY
   ↓ (if no student joins within 15 minutes after start)
3. ABSENT (marked absent, counts towards subscription)
```

### Academy Settings (Default Values):

- **default_preparation_minutes**: 10 (when to transition to READY)
- **default_buffer_minutes**: 5 (grace period after end before auto-complete)
- **default_late_tolerance_minutes**: 15 (how long to wait before marking absent)

---

## Troubleshooting

### Problem: Sessions stuck in SCHEDULED status

**Solution:**
1. Check if scheduler is running: `launchctl list | grep itqan`
2. If not running, start it with Option 1 or Option 2 above
3. Manually fix stuck sessions: `php artisan sessions:update-statuses --verbose`

### Problem: Meeting link not created

**Solution:**
1. Check if session is in READY status: `php artisan tinker --execute="print_r(\App\Models\QuranSession::find(SESSION_ID)->status);"`
2. If status is SCHEDULED but should be READY, run: `php artisan sessions:update-statuses`
3. Check LiveKit configuration in `.env`:
   ```
   LIVEKIT_API_KEY=your_key
   LIVEKIT_API_SECRET=your_secret
   LIVEKIT_URL=wss://your-livekit-server.com
   ```

### Problem: Session not auto-completing

**Solution:**
1. Ensure scheduler is running
2. Check if enough time has passed (scheduled_at + duration + 5 minutes)
3. Manually complete if needed: `php artisan sessions:update-statuses --verbose`

### Problem: LaunchAgent not starting

**Solution:**
1. Check plist file syntax: `plutil -lint ~/Library/LaunchAgents/com.itqan.scheduler.plist`
2. Check logs: `tail -f /Users/abdelrahmanhamdy/web/itqan-platform/storage/logs/scheduler-launchd-error.log`
3. Ensure paths in plist file are correct (absolute paths)
4. Check permissions: `chmod +x /Users/abdelrahmanhamdy/web/itqan-platform/scheduler-worker.sh`

### Problem: "جاري تحضير الاجتماع" shows forever

**Cause:** Meeting preparation logic depends on:
1. Session being in READY status
2. Meeting link being created
3. LiveKit server being accessible

**Solution:**
1. Run: `php artisan sessions:update-statuses --verbose` to transition to READY
2. Check logs: `tail -f storage/logs/cron/sessions:manage-meetings.log`
3. Verify LiveKit connection: `php artisan tinker --execute="app(\App\Services\LiveKitService::class)->testConnection();"`

---

## Viewing Cron Job Logs

All cron commands log to separate files in `storage/logs/cron/`:

```bash
# Main status update command
tail -f storage/logs/cron/sessions:update-statuses.log

# Quran session meetings
tail -f storage/logs/cron/sessions:manage-meetings.log

# Academic session meetings
tail -f storage/logs/cron/academic-sessions:manage-meetings.log

# Meeting creation
tail -f storage/logs/cron/meetings:create-scheduled.log

# Meeting cleanup
tail -f storage/logs/cron/meetings:cleanup-expired.log
```

**Search for errors across all cron logs:**

```bash
grep -r "FAILED\|ERROR\|Exception" storage/logs/cron/
```

---

## Testing the Scheduler

### Test 1: Verify scheduler is running

```bash
# This should show output every minute
tail -f storage/logs/scheduler-worker.log
```

### Test 2: Create a test session and watch it progress

```bash
# Create a session scheduled for 2 minutes from now
php artisan tinker

# In tinker:
$session = App\Models\QuranSession::create([
    'academy_id' => 1,
    'quran_teacher_id' => 3,
    'circle_id' => 2,
    'session_type' => 'group',
    'status' => App\Enums\SessionStatus::SCHEDULED,
    'scheduled_at' => now()->addMinutes(2),
    'duration_minutes' => 30,
    'title' => 'Test Session',
]);

echo "Created session ID: " . $session->id;
exit;
```

Then watch the logs to see it transition:
```bash
tail -f storage/logs/cron/sessions:update-statuses.log
```

You should see:
- After ~2 minutes: Transition to READY
- Meeting link created
- After 30 minutes: Auto-complete to COMPLETED

---

## Production Deployment

For production, set up system cron instead:

```bash
# Edit crontab
crontab -e

# Add this line:
* * * * * cd /path/to/itqan-platform && php artisan schedule:run >> /dev/null 2>&1
```

**Note:** Also update `routes/console.php` to use production timing:
- Change `everyMinute()` to `everyThreeMinutes()` or `everyFiveMinutes()`

---

## Quick Reference Commands

```bash
# Start scheduler (simple terminal method)
./run-scheduler.sh

# Start scheduler (background LaunchAgent)
launchctl load ~/Library/LaunchAgents/com.itqan.scheduler.plist
launchctl start com.itqan.scheduler

# Stop scheduler (background LaunchAgent)
launchctl stop com.itqan.scheduler

# Fix stuck sessions immediately
php artisan sessions:update-statuses --verbose

# View all scheduled commands
php artisan schedule:list

# Check if a session should transition
php artisan tinker --execute="
\$session = App\Models\QuranSession::find(68);
\$service = app(App\Services\SessionStatusService::class);
echo 'Should Ready: ' . (\$service->shouldTransitionToReady(\$session) ? 'YES' : 'NO') . PHP_EOL;
echo 'Should Complete: ' . (\$service->shouldAutoComplete(\$session) ? 'YES' : 'NO') . PHP_EOL;
"
```

---

## Summary

✅ **For daily development:** Use `./run-scheduler.sh` in a terminal window

✅ **For always-on setup:** Use the LaunchAgent (Option 2)

✅ **For debugging:** Run commands manually (Option 3)

🎯 **The key point:** Laravel scheduler MUST be running for sessions to work properly!

---

*Last updated: November 13, 2025*
