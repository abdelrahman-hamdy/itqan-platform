# LiveKit Webhook Setup Guide

## Problem
LiveKit Cloud (`test-rn3dlic1.livekit.cloud`) cannot send webhooks to your local development domain (`itqan-platform.test`) because it's not publicly accessible.

## Solution Options

### Option 1: Use ngrok (Recommended for Development)

#### 1. Install ngrok
Download from: https://ngrok.com/download

For macOS:
```bash
brew install ngrok/ngrok/ngrok
```

#### 2. Create ngrok account and authenticate
```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN
```

#### 3. Start ngrok tunnel
```bash
# In a separate terminal, run:
ngrok http 80 --host-header=itqan-platform.test
```

This will give you a public URL like: `https://abc123.ngrok.io`

#### 4. Configure LiveKit Cloud Dashboard

Go to: https://cloud.livekit.io/projects/test-rn3dlic1/settings/webhooks

Add webhook URL:
```
https://abc123.ngrok.io/webhooks/livekit
```

Events to enable:
- ✅ `participant_joined`
- ✅ `participant_left`
- ✅ `room_started` (optional)
- ✅ `room_finished` (optional)

#### 5. Verify webhook signature key
Copy the webhook signature key from LiveKit dashboard and add to `.env`:
```bash
LIVEKIT_WEBHOOK_KEY=your_webhook_signature_key_here
```

---

### Option 2: Deploy to Staging Server

If you have a staging server with a public domain:

1. Deploy application to staging
2. Configure webhook URL in LiveKit dashboard:
   ```
   https://staging.yourdomain.com/webhooks/livekit
   ```

---

### Option 3: Disable Signature Verification (Local Testing ONLY)

**⚠️ WARNING: Only for local development testing! Never use in production!**

Temporarily disable webhook signature verification to test with manual webhook calls:

Edit `app/Http/Controllers/LiveKitWebhookController.php`:

```php
public function handleWebhook(Request $request): Response
{
    // ... existing logging code ...

    // TEMP: Skip signature validation for local testing
    if (config('app.env') === 'local' && !$this->validateWebhookSignature($request)) {
        Log::warning('⚠️ LOCAL DEV: Webhook signature validation skipped');
    } elseif (config('app.env') !== 'local' && !$this->validateWebhookSignature($request)) {
        return response('Unauthorized', 401);
    }

    // ... rest of handler ...
}
```

---

## Testing Webhook Endpoint

### Test 1: Check endpoint is accessible
```bash
curl -X POST http://itqan-platform.test/webhooks/livekit \
  -H "Content-Type: application/json" \
  -d '{"event":"test"}'
```

### Test 2: Simulate participant_joined webhook
```bash
php artisan tinker
```

Then run:
```php
$request = Illuminate\Http\Request::create(
    '/webhooks/livekit',
    'POST',
    [
        'event' => 'participant_joined',
        'id' => 'test-' . uniqid(),
        'createdAt' => now()->timestamp,
        'room' => [
            'name' => 'session-121-quran',
            'sid' => 'RM_test123',
            'num_participants' => 1,
        ],
        'participant' => [
            'sid' => 'PA_test123',
            'identity' => 'itqan_1',
            'name' => 'Test User',
            'joinedAt' => now()->timestamp,
        ],
    ]
);

$controller = app(App\Http\Controllers\LiveKitWebhookController::class);
$response = $controller->handleWebhook($request);

echo "Response: " . $response->getStatusCode() . PHP_EOL;
```

### Test 3: Monitor webhook reception in real-time
```bash
# Terminal 1: Watch logs
tail -f storage/logs/laravel.log | grep "WEBHOOK"

# Terminal 2: Run test webhook
php test-webhook-simulation.php
```

---

## Debugging Webhook Issues

### Check 1: Verify endpoint is registered
```bash
php artisan route:list | grep webhook
```

Should show:
```
POST webhooks/livekit
```

### Check 2: Check recent webhook logs
```bash
tail -100 storage/logs/laravel.log | grep "🔔 WEBHOOK ENDPOINT HIT"
```

If you see no logs, webhooks are not reaching your application.

### Check 3: Test webhook signature validation
```bash
php artisan tinker
```

```php
$secret = config('livekit.api_secret');
$body = json_encode(['event' => 'test']);
$signature = hash_hmac('sha256', $body, $secret);

echo "Expected signature: " . $signature . PHP_EOL;
```

### Check 4: View attendance events
```bash
php artisan attendance:debug --watch
```

---

## Webhook Flow Diagram

```
┌─────────────────────────────────┐
│     LiveKit Cloud               │
│  (test-rn3dlic1.livekit.cloud)  │
└─────────────────────────────────┘
                ↓ HTTPS POST
         (needs public URL)
                ↓
┌─────────────────────────────────┐
│         ngrok Tunnel            │
│   https://abc123.ngrok.io       │
└─────────────────────────────────┘
                ↓
┌─────────────────────────────────┐
│    Local Application            │
│   itqan-platform.test:80        │
│   POST /webhooks/livekit        │
└─────────────────────────────────┘
                ↓
┌─────────────────────────────────┐
│  LiveKitWebhookController       │
│  1. Validate signature          │
│  2. Parse event data            │
│  3. Call AttendanceEventService │
└─────────────────────────────────┘
                ↓
┌────────────────┬────────────────┐
│ MeetingAttend..│ MeetingAttend..│
│ Event (log)    │ ance (state)   │
└────────────────┴────────────────┘
```

---

## Quick Start Checklist

- [ ] Install ngrok
- [ ] Start ngrok tunnel: `ngrok http 80 --host-header=itqan-platform.test`
- [ ] Copy ngrok URL (e.g., `https://abc123.ngrok.io`)
- [ ] Go to LiveKit Cloud dashboard: https://cloud.livekit.io/
- [ ] Navigate to: Settings → Webhooks
- [ ] Add webhook URL: `https://abc123.ngrok.io/webhooks/livekit`
- [ ] Enable events: `participant_joined`, `participant_left`
- [ ] Copy webhook signature key
- [ ] Add to `.env`: `LIVEKIT_WEBHOOK_KEY=your_key_here`
- [ ] Restart Laravel: `php artisan config:clear`
- [ ] Test: Join a meeting and watch logs: `php artisan attendance:debug --watch`

---

## Expected Behavior After Setup

When a user joins/leaves a meeting:

1. **LiveKit Cloud** sends webhook to ngrok URL
2. **ngrok** forwards to your local application
3. **LiveKitWebhookController** logs: `🔔 WEBHOOK ENDPOINT HIT`
4. **AttendanceEventService** creates/updates records
5. **Database** stores:
   - `meeting_attendance_events` - New event record
   - `meeting_attendances` - Updated join/leave cycles
6. **attendance:debug** command shows the event immediately

---

## Alternative: Production/Staging Deployment

If ngrok is not suitable for your workflow, deploy to a server with a public domain:

**Webhook URL:**
```
https://your-staging-domain.com/webhooks/livekit
```

**Benefits:**
- No tunneling required
- Stable URL (doesn't change on restart)
- Better performance
- Production-like environment

**Setup:**
1. Deploy application to staging server
2. Configure webhook URL in LiveKit dashboard
3. Add `LIVEKIT_WEBHOOK_KEY` to staging `.env`
4. Monitor: `ssh your-server "tail -f /path/to/storage/logs/laravel.log | grep WEBHOOK"`
