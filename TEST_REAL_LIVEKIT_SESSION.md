# Testing Real LiveKit Session - Step by Step

## Current Status
- ✅ Room name generation is ALREADY dynamic (uses academy subdomain + session type)
- ✅ ngrok is running
- ❌ Webhooks not being received (need to configure LiveKit dashboard)

## Step 1: Get Your ngrok URL

```bash
curl http://localhost:4040/api/tunnels | jq -r '.tunnels[0].public_url'
```

OR visit: http://localhost:4040/status

You should see something like: `https://percolative-unyielded-taneka.ngrok-free.dev`

## Step 2: Configure LiveKit Cloud Webhook

1. Go to: https://cloud.livekit.io/
2. Login to your account
3. Go to your project settings
4. Find "Webhooks" section
5. Add new webhook:
   - **URL**: `<YOUR_NGROK_URL>/webhooks/livekit`
   - Example: `https://percolative-unyielded-taneka.ngrok-free.dev/webhooks/livekit`
   - **Events to enable**:
     - ✅ participant_joined
     - ✅ participant_left

**IMPORTANT**: Each time you restart ngrok, the URL changes and you MUST update it in LiveKit dashboard!

## Step 3: Join a Real Session

### Option A: Via Browser (Recommended)

1. Open your browser
2. Go to: `https://itqan-academy.itqan-platform.test/teacher-panel/calendar`
3. Find session 121 (or any quran session)
4. Click "Join Session"
5. Allow camera/microphone
6. Stay in the session for 2-3 minutes
7. Leave the session

### Option B: Via Student Portal

1. Login as a student
2. Go to Quran Sessions
3. Join session 121
4. Stay for 2-3 minutes
5. Leave

## Step 4: Monitor Webhooks

Open 3 terminals:

### Terminal 1: ngrok (already running)
```bash
# Should already be running from before
# If not:
ngrok http 80 --host-header=itqan-platform.test
```

### Terminal 2: Watch Webhooks
```bash
./watch-webhooks.sh
```

### Terminal 3: Watch Attendance
```bash
php artisan attendance:debug 121 --watch
```

## Step 5: What You Should See

### When you JOIN the session:
```
Terminal 2 (webhooks):
[timestamp] WEBHOOK ENDPOINT HIT - Request received
[timestamp] LiveKit webhook received {"event":"participant_joined"...}

Terminal 3 (attendance):
=== LiveKit Webhook Activity (Last 5 Minutes) ===
| Time     | Event  | Session | User        |
| XX:XX:XX | ✓ JOIN | #121    | Your Name   |
```

### When you LEAVE the session:
```
Terminal 2 (webhooks):
[timestamp] LiveKit webhook received {"event":"participant_left"...}

Terminal 3 (attendance):
| Time     | Event   | Session | User      | Duration |
| XX:XX:XX | ✗ LEAVE | #121    | Your Name | 3min     |
```

## Troubleshooting

### No Webhooks Received?

1. **Check ngrok URL is correct in LiveKit dashboard**
   ```bash
   # Get current ngrok URL
   curl http://localhost:4040/api/tunnels | jq -r '.tunnels[0].public_url'
   ```

2. **Test ngrok endpoint directly**
   ```bash
   curl -X POST <YOUR_NGROK_URL>/webhooks/livekit
   ```
   Should return: "OK" or method not allowed

3. **Check LiveKit dashboard webhook settings**
   - Webhook URL should end with `/webhooks/livekit`
   - Events `participant_joined` and `participant_left` should be enabled

4. **Check ngrok is forwarding to port 80 (not 443)**
   ```bash
   ps aux | grep ngrok
   # Should show: ngrok http 80
   ```

### Webhooks Received But No Duration?

1. **Check participant identity format**
   - Should be: `{userId}_{firstName}_{lastName}`
   - Example: `1_Ameer_Maher`
   - This is set in LiveKitService when creating tokens

2. **Check room name matches**
   ```bash
   php artisan tinker
   >>> $session = QuranSession::find(121)
   >>> $session->meeting_room_name
   ```

### Still Not Working?

1. **Check Laravel logs**
   ```bash
   tail -f storage/logs/laravel.log | grep -E "WEBHOOK|LiveKit"
   ```

2. **Check ngrok web interface**
   Visit: http://localhost:4040
   Look for POST requests to `/webhooks/livekit`

3. **Check .env settings**
   ```bash
   grep LIVEKIT .env
   ```
   Should show your LiveKit API key and secret

## Important Notes

### Room Naming is ALREADY Dynamic
The system ALREADY uses dynamic room names:
```php
// Format: {academy-subdomain}-{session-type}-session-{id}
// Examples:
itqan-academy-quran-session-121
another-academy-academic-session-456
test-academy-interactive-session-789
```

### Test Scripts vs Real Sessions
- **Test scripts**: Simulate webhooks without LiveKit (for development)
- **Real sessions**: Actual LiveKit meetings with real webhooks

For production testing, you MUST join a real session via browser.

## Next Steps After Testing

Once you confirm webhooks are working:

1. **For Production**: Configure webhook with your production domain
2. **Change queue back to database**: `QUEUE_CONNECTION=database` in .env
3. **Restart queue workers**: `php artisan queue:restart`

---

**Remember**: ngrok URL changes every time you restart ngrok!
Always update the webhook URL in LiveKit Cloud dashboard after restarting ngrok.
