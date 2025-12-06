# 🔧 Notification System - Troubleshooting Guide

**Last Updated:** December 2, 2025

---

## ⚠️ CRITICAL ISSUE IDENTIFIED

**ROOT CAUSE:** The Laravel Scheduler was NOT running, which prevented automatic session notifications from being created.

---

## 🚀 Required Services (ALL THREE MUST RUN)

### 1. Reverb (Real-time WebSocket) - Optional but recommended
```bash
php artisan reverb:start --host=0.0.0.0 --port=8085
```
**Purpose:** Broadcasts notifications in real-time to connected users
**Impact if not running:** Notifications still saved to database, but won't appear until page refresh

### 2. Queue Worker - REQUIRED for email notifications
```bash
php artisan queue:listen --tries=1 --timeout=90
```
**Purpose:** Processes background jobs (emails, SMS, etc.)
**Impact if not running:** Email notifications won't be sent

### 3. Scheduler - ⚠️ CRITICAL FOR SESSION NOTIFICATIONS
```bash
php artisan schedule:work
```
**Purpose:** Runs scheduled commands every minute, including:
- `sessions:update-statuses` → Updates session status → Triggers notifications
- `subscriptions:check-expiring` → Sends expiry warnings
- Session management commands

**Impact if not running:**
- ❌ NO session scheduled/ready/started/completed notifications
- ❌ NO subscription expiry notifications
- ❌ NO automatic attendance calculations

---

## ✅ Quick Start - Run All Services

```bash
# Option 1: Use the automated script
chmod +x start-all-services.sh
./start-all-services.sh

# Option 2: Manual start (run each in separate terminal)
php artisan reverb:start --host=0.0.0.0 --port=8085
php artisan queue:listen --tries=1 --timeout=90
php artisan schedule:work

# Option 3: Use composer dev (if configured)
composer dev
```

### Verify Services Are Running

```bash
# Check all services
ps aux | grep -E "reverb:start|queue:listen|schedule:work" | grep -v grep

# Should show:
# - php artisan reverb:start
# - php artisan queue:listen
# - php artisan schedule:work
```

---

## 📊 How Notifications Work

### Automatic Notifications (require Scheduler)

| Action | Triggered By | Notification Sent When |
|--------|--------------|----------------------|
| Session Scheduled | `sessions:update-statuses` | Session becomes READY (30 min before) |
| Session Started | `sessions:update-statuses` | Session starts |
| Session Completed | `sessions:update-statuses` | Session ends |
| Attendance Calculated | Attendance service | After session completes |
| Subscription Expiring | `subscriptions:check-expiring` | Daily at 9:00 AM (7, 3, 1 days before) |

### Manual Notifications (triggered by user actions)

| Action | Triggered By | Notification Sent When |
|--------|--------------|----------------------|
| Homework Assigned | Model update event | Teacher sets `homework_assigned = true` |
| Payment Success/Fail | Payment processing | Payment gateway returns result |

---

## 🧪 Testing Notifications

### Test 1: Core Notification System (Should ALWAYS work)
```bash
php artisan tinker --execute="
\$user = \App\Models\User::first();
\$service = app(\App\Services\NotificationService::class);
\$service->send(
    \$user,
    \App\Enums\NotificationType::SESSION_SCHEDULED,
    ['session_title' => 'Test Session'],
    '/sessions/123'
);
echo 'Notification created. Total: ' . \DB::table('notifications')->count();
"
```
**Expected:** Creates notification in database immediately

### Test 2: Homework Notification (Model event)
```bash
php artisan tinker --execute="
\$session = \App\Models\AcademicSession::whereNotNull('student_id')->first();
if (\$session) {
    \$before = \DB::table('notifications')->count();
    \$session->update(['homework_assigned' => true]);
    \$after = \DB::table('notifications')->count();
    echo 'Created ' . (\$after - \$before) . ' notifications';
}
"
```
**Expected:** Creates homework notification when session updated

### Test 3: Payment Notification
```bash
# Process a test payment through the application
# Check if notification was created:
php artisan tinker --execute="
\$latest = \DB::table('notifications')
    ->where('notification_type', 'payment_success')
    ->orWhere('notification_type', 'payment_failed')
    ->latest()
    ->first();
if (\$latest) {
    echo 'Found payment notification: ' . \$latest->notification_type;
} else {
    echo 'No payment notifications found';
}
"
```

### Test 4: Session Status Notifications (requires Scheduler)
```bash
# Manual trigger
php artisan sessions:update-statuses

# Check logs
tail -f storage/logs/scheduler.log | grep notification
```

### Test 5: Subscription Expiry Notifications
```bash
# Manual trigger
php artisan subscriptions:check-expiring

# Check created notifications
php artisan tinker --execute="
\$count = \DB::table('notifications')
    ->where('notification_type', 'subscription_expiring')
    ->count();
echo 'Subscription expiry notifications: ' . \$count;
"
```

---

## 🔍 Debugging Checklist

### Problem: NO notifications appearing at all

- [ ] Check if NotificationService works directly (Test 1 above)
- [ ] If Test 1 fails: Database issue or NotificationService broken
- [ ] If Test 1 works: Services not running or events not firing

### Problem: Notifications work in tests but not in UI

- [ ] Check services are running: `ps aux | grep -E "reverb|queue|schedule"`
- [ ] Check scheduler is running: `ps aux | grep schedule:work`
- [ ] Check logs: `tail -f storage/logs/scheduler.log`
- [ ] Manually run: `php artisan sessions:update-statuses`

### Problem: Homework notifications not working

- [ ] Check if `homework_assigned` field is being set
- [ ] Verify model event listener is registered
- [ ] Test manually with Test 2 above
- [ ] Check if homework is assigned through Filament or different method

### Problem: Payment notifications not working

- [ ] Check if payments are being processed
- [ ] Verify PaymentService is being used
- [ ] Check payment status after processing
- [ ] Look for errors in logs

### Problem: Real-time notifications not appearing (but database has them)

- [ ] Check Reverb is running: `ps aux | grep reverb`
- [ ] Check browser console for WebSocket errors
- [ ] Verify Echo configuration in `resources/js/app.js`
- [ ] Check if user is subscribed to correct channel

---

## 📝 Common Issues & Solutions

### Issue 1: "Notifications don't appear on page load"
**Cause:** Frontend not loading from database
**Solution:** Check `NotificationCenter` Livewire component is rendering
**Verify:** View page source, search for "notification-center"

### Issue 2: "Sessions don't send notifications"
**Cause:** Scheduler not running
**Solution:** Run `php artisan schedule:work` in background
**Verify:** `ps aux | grep schedule:work` shows running process

### Issue 3: "Homework notifications not sent when assigned"
**Cause:** Homework field not being updated correctly
**Solution:** Ensure `homework_assigned` is set to `true` when assigning
**Check:** Model event listeners in `QuranSession::boot()` and `AcademicSession::boot()`

### Issue 4: "Payment notifications not sent"
**Cause:** PaymentService not calling notification method
**Solution:** Verify `sendPaymentNotifications()` is called in `updatePaymentFromResult()`
**Check:** Look for notification in database after processing test payment

### Issue 5: "Duplicate notifications"
**Cause:** Multiple services running or model event firing twice
**Solution:** Kill duplicate processes: `pkill -f schedule:work; ./start-all-services.sh`
**Prevent:** Only run services once

---

## 📊 Monitoring Commands

```bash
# Count total notifications
php artisan tinker --execute="echo \DB::table('notifications')->count();"

# Count by type
php artisan tinker --execute="
\DB::table('notifications')
    ->select('notification_type', \DB::raw('count(*) as count'))
    ->groupBy('notification_type')
    ->get()
    ->each(function(\$row) {
        echo \$row->notification_type . ': ' . \$row->count . PHP_EOL;
    });
"

# Recent notifications
php artisan tinker --execute="
\DB::table('notifications')
    ->latest()
    ->limit(10)
    ->get(['notification_type', 'created_at', 'read_at'])
    ->each(function(\$n) {
        echo \$n->created_at . ' - ' . \$n->notification_type . PHP_EOL;
    });
"

# Unread count for user
php artisan tinker --execute="
echo \DB::table('notifications')
    ->where('notifiable_id', 1)
    ->whereNull('read_at')
    ->count();
"

# Watch scheduler in real-time
tail -f storage/logs/scheduler.log

# Watch all Laravel logs
php artisan pail
```

---

## 🎯 Production Deployment

### Supervisor Configuration (Recommended)

Create `/etc/supervisor/conf.d/itqan-services.conf`:

```ini
[program:itqan-reverb]
command=php /path/to/artisan reverb:start --host=0.0.0.0 --port=8085
directory=/path/to/project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/reverb.log

[program:itqan-queue]
command=php /path/to/artisan queue:listen --tries=3 --timeout=90
directory=/path/to/project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/queue.log

[program:itqan-scheduler]
command=php /path/to/artisan schedule:work
directory=/path/to/project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/scheduler.log
```

### Cron Job (Alternative to schedule:work)

Add to crontab (`crontab -e`):
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## ✅ Final Checklist

- [x] Reverb running (real-time)
- [x] Queue worker running (emails)
- [x] Scheduler running (session notifications)
- [ ] Test core notification creation
- [ ] Test homework notification
- [ ] Test payment notification
- [ ] Test subscription expiry notification
- [ ] Verify notifications appear in UI
- [ ] Verify real-time updates work
- [ ] Configure for production (Supervisor/Cron)

---

## 🆘 Still Not Working?

1. Check Laravel logs: `php artisan pail` or `tail -f storage/logs/laravel.log`
2. Check service logs: `tail -f storage/logs/scheduler.log`
3. Run test commands above to isolate the issue
4. Verify database table structure: `php artisan tinker --execute="\DB::select('DESCRIBE notifications');"`
5. Check if events are registered: Look for `boot()` methods in models

---

**Remember:** The scheduler (`php artisan schedule:work`) is CRITICAL for session notifications to work!
