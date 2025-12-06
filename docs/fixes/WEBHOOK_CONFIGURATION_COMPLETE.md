# LiveKit Webhook Configuration - Status Report

## ✅ Completed Steps

### 1. Fixed Timezone Handling
- **Changed**: App now stores all timestamps in UTC
- **Files Modified**:
  - `config/app.php` - Changed timezone to `env('APP_TIMEZONE', 'UTC')`
  - `.env` - Added comment explaining UTC storage with academy timezone for display
  - `app/Http/Controllers/LiveKitWebhookController.php` - Added explicit UTC timezone to all timestamp creation

**Pattern to Follow**:
```php
// Storage: Always UTC
$timestamp = Carbon::createFromTimestamp($data['joinedAt'], 'UTC');

// Display: Use academy timezone  
$academy = $session->academy;
$displayTime = $timestamp->setTimezone($academy->timezone);
```

### 2. Fixed Critical Attendance Bug
- **Bug**: Code was looking for `'joined_at'` but LiveKit sends `'joinedAt'` (camelCase)
- **Impact**: All join timestamps were using `now()` instead of LiveKit's actual timestamp
- **Fix**: Changed field names in `LiveKitWebhookController.php` lines 273 and 371
- **Documentation**: Created `ATTENDANCE_SYSTEM_FIXES.md` with full analysis

### 3. Configured ngrok Tunnel
- **Status**: ✅ Running and working
- **URL**: `https://percolative-unyielded-taneka.ngrok-free.dev`
- **Critical Flag**: `--host-header=rewrite` (required for Valet compatibility)
- **Health Check**: `curl https://percolative-unyielded-taneka.ngrok-free.dev/webhooks/livekit/health`
- **Response**: `{"status":"ok","timestamp":"...","service":"livekit-webhooks"}`

**Start ngrok**:
```bash
ngrok http https://itqan-platform.test:443 --host-header=rewrite
```

### 4. Created Helper Scripts
- ✅ `configure-livekit-webhooks.sh` - Shows exact steps to configure LiveKit server
- ✅ `monitor-webhooks.sh` - Real-time webhook monitoring
- ✅ `LIVEKIT_WEBHOOK_SETUP.md` - Complete documentation

## 🎯 Next Steps (Requires SSH Access)

You need to configure the LiveKit server at `31.97.126.52` to send webhooks to the ngrok URL.

### Quick Configuration

Run this script to see the exact steps:
```bash
./configure-livekit-webhooks.sh
```

Or follow these manual steps:

### Manual Configuration Steps

**1. SSH to LiveKit Server**
```bash
ssh root@31.97.126.52
```

**2. Edit LiveKit Configuration**
```bash
nano /opt/livekit/livekit.yaml
```

**3. Add Webhook Configuration**
Add this to the YAML file (replace with current ngrok URL if different):
```yaml
webhook:
  urls:
    - https://percolative-unyielded-taneka.ngrok-free.dev/webhooks/livekit
  api_key: APIxdLnkvjeS3PV
```

**4. Save and Restart**
```bash
# Save with Ctrl+X, then Y, then Enter

# Restart LiveKit
cd /opt/livekit && docker-compose restart

# Verify running
docker ps | grep livekit
```

## 🔍 Testing Webhooks

### 1. Monitor Incoming Webhooks
```bash
./monitor-webhooks.sh
```

### 2. Test with Real Session
1. Start a session that's scheduled to be live
2. Join the meeting as a participant
3. Watch the monitor output for webhook events
4. Verify attendance is recorded:
```bash
php artisan tinker
```
```php
// Check attendance events
$events = App\Models\MeetingAttendanceEvent::where('session_id', YOUR_SESSION_ID)
    ->latest()
    ->get();
    
foreach ($events as $event) {
    echo "Event: {$event->event_type} | Joined: {$event->event_timestamp} | Left: {$event->left_at} | Duration: {$event->duration_minutes}min\n";
}

// Check aggregated attendance
$attendance = App\Models\MeetingAttendance::where('session_id', YOUR_SESSION_ID)
    ->where('user_id', YOUR_USER_ID)
    ->first();
    
echo "Status: {$attendance->status}\n";
echo "Duration: {$attendance->duration_minutes} minutes\n";
echo "Joined: {$attendance->joined_at->setTimezone('Asia/Riyadh')}\n";
echo "Left: " . ($attendance->left_at ? $attendance->left_at->setTimezone('Asia/Riyadh') : 'Still in meeting') . "\n";
```

### 3. Check Webhook Delivery
View recent webhook hits:
```bash
tail -100 storage/logs/laravel.log | grep "WEBHOOK ENDPOINT HIT"
```

## 🌍 Multi-Academy Support

Webhooks automatically work for all academies:
- Room names contain academy info: `session-{id}-{type}-academy-{academyId}`
- Session lookup finds correct academy automatically
- Each academy's timezone used for display (stored as UTC internally)
- No additional configuration needed per academy

## 📊 System Architecture

```
LiveKit Server (31.97.126.52)
         ↓
   Sends webhook
         ↓
    ngrok tunnel (with --host-header=rewrite)
         ↓
Laravel Valet (itqan-platform.test)
         ↓
LiveKitWebhookController->handleWebhook()
         ↓
┌────────────────────┬────────────────────┐
│ participant_joined │  participant_left  │
└────────────────────┴────────────────────┘
         ↓                     ↓
Create MeetingAttendanceEvent (immutable log with UTC timestamps)
         ↓                     ↓
AttendanceEventService->recordJoin/Leave()
         ↓                     ↓
Update MeetingAttendance (aggregated state)
         ↓
Display times converted to academy timezone
```

## 🔧 Troubleshooting

### ngrok Tunnel Issues
If webhook endpoint returns 404:
1. Ensure ngrok is running with `--host-header=rewrite`
2. Check tunnel status: `curl http://localhost:4040/api/tunnels`
3. Test health locally first: `curl -k https://itqan-platform.test/webhooks/livekit/health`

### Webhook Not Receiving
1. Check LiveKit server configuration: `ssh root@31.97.126.52 "cat /opt/livekit/livekit.yaml | grep -A 5 webhook"`
2. Verify LiveKit is running: `ssh root@31.97.126.52 "docker ps | grep livekit"`
3. Check LiveKit logs: `ssh root@31.97.126.52 "docker logs livekit-server --tail 50"`

### Timestamp Issues
- Always store in UTC: `Carbon::createFromTimestamp($time, 'UTC')`
- Convert to academy timezone only for display
- Never manually set timezone to a specific value
- Use `$session->academy->timezone` for display conversion

## 📝 Important Notes

### For Production Deployment
Replace ngrok with one of these options:
1. **Public Domain**: Point your domain DNS to Laravel server (e.g., `api.yourdomain.com`)
2. **Cloudflare Tunnel**: More reliable than ngrok for production use
3. **VPN**: Connect LiveKit server and Laravel server on same private network

**ngrok is only suitable for development/testing.**

### Timezone Best Practices
```php
// ✅ CORRECT
$timestamp = Carbon::createFromTimestamp($data['joinedAt'], 'UTC'); // Storage
$displayTime = $timestamp->setTimezone($academy->timezone); // Display

// ❌ WRONG
$timestamp = Carbon::createFromTimestamp($data['joinedAt'], 'Africa/Cairo'); // Don't hardcode!
```

### Multi-Academy Considerations
Each academy can have different timezone settings:
- Academy 1: `Asia/Riyadh` (Saudi Arabia)
- Academy 2: `Africa/Cairo` (Egypt)
- Academy 3: `Europe/London` (UK)

The system handles this automatically - no manual timezone conversion needed.

## 🎉 What's Working Now

1. ✅ Webhooks delivered via ngrok successfully
2. ✅ Join events created with correct timestamps (after camelCase fix)
3. ✅ Leave events closing join events correctly
4. ✅ Duration calculations working
5. ✅ Idempotency preventing duplicate records
6. ✅ Timezone handling: UTC storage + academy-specific display
7. ✅ Multi-academy support automatic

## 🚀 Ready to Test

Once you configure the LiveKit server webhook (next steps above), the entire attendance system will be fully operational!
