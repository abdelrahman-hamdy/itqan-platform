# Webhook Issue: Why Attendance is Not Working

## The Problem

You're seeing **duration always 0min** and **"In meeting" status never changing** because:

### Root Cause
**LiveKit Cloud cannot send webhooks to your local development environment.**

Your setup:
- ✅ LiveKit Cloud: `test-rn3dlic1.livekit.cloud` (hosted service)
- ❌ Your app: `itqan-platform.test` (local domain, not accessible from internet)
- ❌ Result: Webhooks never reach your application

### Evidence
```bash
# No webhook logs found
tail -f storage/logs/laravel.log | grep "WEBHOOK ENDPOINT HIT"
# (nothing appears - webhooks not reaching the app)

# No events in database
php artisan tinker
>>> App\Models\MeetingAttendanceEvent::count()
=> 0
```

---

## The Solution

You have 3 options:

### Option 1: Use ngrok (Quickest - 5 minutes)

**Install ngrok:**
```bash
brew install ngrok/ngrok/ngrok
```

**Start tunnel:**
```bash
ngrok http 80 --host-header=itqan-platform.test
```

**Configure LiveKit:**
1. Copy the ngrok URL (e.g., `https://abc123.ngrok.io`)
2. Go to LiveKit Cloud dashboard: https://cloud.livekit.io/
3. Settings → Webhooks → Add webhook
4. Webhook URL: `https://abc123.ngrok.io/webhooks/livekit`
5. Enable events: `participant_joined`, `participant_left`
6. Copy webhook signature key
7. Add to `.env`: `LIVEKIT_WEBHOOK_KEY=your_key_here`
8. Restart app: `php artisan config:clear`

**Test:**
```bash
# Terminal 1: Watch logs
tail -f storage/logs/laravel.log | grep "WEBHOOK"

# Terminal 2: Join a meeting
# You should see webhook logs appear immediately
```

---

### Option 2: Test Locally (No Real Webhooks)

If you can't use ngrok right now, you can test the system with simulated webhooks:

```bash
# Simulate join → wait → leave
php test-webhook-local.php 121 1
```

This will:
- Call the webhook controller directly
- Update MeetingAttendance and MeetingAttendanceEvent tables
- Show duration calculations
- Let you test the system without real LiveKit webhooks

**Then check:**
```bash
php artisan attendance:debug 121
```

---

### Option 3: Deploy to Staging Server

If you have a staging server with a public domain:

1. Deploy app to staging
2. Configure webhook: `https://staging.yourdomain.com/webhooks/livekit`
3. Test with real sessions

---

## Quick Test Right Now

Without changing anything, you can test the attendance system:

```bash
# 1. Run simulation
php test-webhook-local.php 121 1

# 2. Choose option 3 (full cycle)

# 3. Check results
php artisan attendance:debug 121
```

You should see:
```
Session: #121 | Ameer Maher | 22:15:59 | 22:16:09 | 1 | 0.17min
```

---

## Why This Happened

The attendance tracking system is **100% webhook-based** (as you requested):
- ✅ No client-side tracking
- ✅ No API polling
- ✅ No repeated calls
- ✅ Just webhooks → storage → post-meeting calculation

**But webhooks require a public URL** - local domains like `itqan-platform.test` are not accessible from the internet.

---

## Next Steps

Choose one of the solutions above and test. I recommend **Option 1 (ngrok)** for quickest results - takes 5 minutes to set up.

For full setup instructions, see: [WEBHOOK_SETUP_GUIDE.md](WEBHOOK_SETUP_GUIDE.md)
