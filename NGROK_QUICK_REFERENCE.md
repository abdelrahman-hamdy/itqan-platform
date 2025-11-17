# ngrok Quick Reference Card

## Start ngrok Tunnel

```bash
# In a new terminal:
ngrok http 80 --host-header=itqan-platform.test
```

**Keep this terminal open!**

## Configure LiveKit

1. **Get ngrok URL** from the terminal output:
   ```
   Forwarding: https://abc123xyz.ngrok.io -> http://localhost:80
   ```

2. **Add to LiveKit Cloud**:
   - Dashboard: https://cloud.livekit.io/projects/test-rn3dlic1/settings/webhooks
   - Webhook URL: `https://abc123xyz.ngrok.io/webhooks/livekit`
   - Events: ✅ `participant_joined` ✅ `participant_left`

## Testing Webhooks

### Terminal 1: Watch webhook logs
```bash
tail -f storage/logs/laravel.log | grep "🔔 WEBHOOK\|✅ \[WEBHOOK\]"
```

### Terminal 2: Watch attendance
```bash
php artisan attendance:debug 121 --watch
```

### Terminal 3: Join meeting
Open session page in browser and join the LiveKit meeting

## Expected Behavior

When you join a meeting, you should see:

**Terminal 1 (Webhook logs):**
```
[2025-11-14 22:30:15] 🔔 WEBHOOK ENDPOINT HIT - Request received
[2025-11-14 22:30:15] ✅ [WEBHOOK] JOIN event processed
```

**Terminal 2 (Attendance debug):**
```
#121 | Ameer Maher | 22:30:15 | In meeting | 1 | 0min
#121 | Ameer Maher | 22:30:15 | In meeting | 1 | 0.5min
#121 | Ameer Maher | 22:30:15 | In meeting | 1 | 1.2min
```

When you leave:
```
#121 | Ameer Maher | 22:30:15 | 22:31:45 | 1 | 1.5min
```

## Test Webhook Endpoint

```bash
# Test if ngrok is forwarding correctly
./test-ngrok-webhook.sh
```

## Troubleshooting

### Problem: No webhook logs appearing

**Check 1:** Is ngrok running?
```bash
curl -s http://127.0.0.1:4040/api/tunnels | grep -o '"public_url":"[^"]*"'
```

**Check 2:** Is Laravel running?
```bash
curl -I http://itqan-platform.test/webhooks/livekit
```

**Check 3:** Test webhook endpoint
```bash
./test-ngrok-webhook.sh
```

### Problem: Webhook logs show but no attendance records

**Check:** Did you configure the session correctly?
```bash
php artisan tinker
>>> \App\Models\QuranSession::find(121)
>>> \App\Models\User::find(1)
```

### Problem: ngrok URL changes on restart

**Solution:** Use ngrok paid plan for static URLs, or update LiveKit webhook URL each time

## Important Notes

- ⚠️ **ngrok must stay running** - if you close the terminal, webhooks stop working
- ⚠️ **Free ngrok URLs change** - update LiveKit webhook URL if you restart ngrok
- ⚠️ **Development only** - use a proper domain in production

## Commands Reference

```bash
# Start ngrok
ngrok http 80 --host-header=itqan-platform.test

# Test webhook
./test-ngrok-webhook.sh

# Watch webhook logs
tail -f storage/logs/laravel.log | grep "WEBHOOK"

# Watch attendance
php artisan attendance:debug 121 --watch

# View attendance data
php artisan attendance:debug 121

# Simulate webhook locally (no ngrok needed)
php test-webhook-local.php 121 1
```

## Quick Setup Checklist

- [ ] Sign up at https://dashboard.ngrok.com/signup
- [ ] Get auth token from https://dashboard.ngrok.com/get-started/your-authtoken  
- [ ] Run: `ngrok config add-authtoken YOUR_TOKEN`
- [ ] Start tunnel: `ngrok http 80 --host-header=itqan-platform.test`
- [ ] Copy ngrok URL (e.g., `https://abc123.ngrok.io`)
- [ ] Add webhook to LiveKit: `https://YOUR_NGROK_URL/webhooks/livekit`
- [ ] Enable events: participant_joined, participant_left
- [ ] Test: `./test-ngrok-webhook.sh`
- [ ] Join meeting and watch logs

---

**Need help?** Check [WEBHOOK_SETUP_GUIDE.md](WEBHOOK_SETUP_GUIDE.md) for detailed instructions.
