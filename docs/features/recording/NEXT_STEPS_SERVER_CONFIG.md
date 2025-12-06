# Next Steps: Server Configuration

## Current Status ✅

**Laravel Application**: 100% Complete
- ✅ RecordingService integrated into controllers
- ✅ All routes added (download, stream, start, stop)
- ✅ All tests passing (9/9)
- ✅ Database schema ready
- ✅ Webhook handler ready
- ✅ Webhook endpoint verified: `https://itqan-platform.test/webhooks/livekit/health` ✅

**Server Configuration**: Pending ⏳

> **📘 For comprehensive manual instructions with troubleshooting, see [SERVER_CONFIGURATION_MANUAL.md](SERVER_CONFIGURATION_MANUAL.md)**

---

## What You Need to Do Now

### Step 1: Transfer Setup Script to Server

**From your local machine**, run:

```bash
scp scripts/deployment/finalize-recording-setup.sh \
    root@31.97.126.52:/opt/livekit/conference.itqanway.com/
```

**Expected output**:
```
finalize-recording-setup.sh   100%   3.5KB   1.2MB/s   00:00
```

---

### Step 2: SSH into Server

```bash
ssh root@31.97.126.52
```

---

### Step 3: Run Configuration Script

```bash
cd /opt/livekit/conference.itqanway.com
chmod +x finalize-recording-setup.sh
bash finalize-recording-setup.sh
```

**Expected output**:
```
=== LiveKit Recording Feature - Final Setup ===

Step 1: Extracting API credentials from livekit.yaml...
API Key: APIDATWRbyzZbxf
API Secret: QcWYF4rTCJy9ekds...

Step 2: Updating egress.yaml with correct API credentials...
✅ egress.yaml updated

Step 3: Configuring webhook URL in livekit.yaml...
✅ Webhook configuration added to livekit.yaml

Step 4: Restarting services to apply changes...
Waiting for services to restart...

Step 5: Verifying services...

Container Status:
NAME                IMAGE                          STATUS
livekit-redis       redis:7-alpine                 Up 15 seconds (healthy)
livekit-server      livekit/livekit-server:latest  Up 12 seconds (healthy)
livekit-egress      livekit/egress:latest          Up 10 seconds (healthy)
livekit-nginx       nginx:alpine                   Up 8 seconds (healthy)

=== Setup Complete! ===

Next steps:
1. Test webhook endpoint: curl https://itqan-platform.test/webhooks/livekit/health
2. Create a test recording from an Interactive Course session
3. Check recordings directory: ls -lh /opt/livekit/conference.itqanway.com/recordings/
```

---

### Step 4: Verify Configuration

#### 4.1 Check Egress Config

```bash
cat /opt/livekit/conference.itqanway.com/egress.yaml
```

**Expected**:
```yaml
# LiveKit Egress Configuration
# Purpose: Recording interactive course sessions

# API Credentials (must match livekit.yaml)
api_key: APIDATWRbyzZbxf
api_secret: QcWYF4rTCJy9ekdsfW3bsgg1wpGUeWmsYtBIEoG12EGA

# LiveKit Server URL (localhost via host network)
ws_url: ws://127.0.0.1:7880

# Redis (localhost via host network)
redis:
  address: 127.0.0.1:6379

# Local file storage
file_output:
  local:
    enabled: true
    output_directory: /recordings

# Logging
log_level: info

# Health check
health_port: 9090
```

#### 4.2 Check Webhook Config

```bash
grep -A 5 "webhook:" /opt/livekit/conference.itqanway.com/livekit.yaml
```

**Expected**:
```yaml
webhook:
  api_key: APIDATWRbyzZbxf
  urls:
    - https://itqan-platform.test/webhooks/livekit
```

#### 4.3 Verify Services Running

```bash
docker compose ps
```

**Expected** (all should show "Up X seconds (healthy)"):
```
NAME                IMAGE                          STATUS
livekit-redis       redis:7-alpine                 Up X seconds (healthy)
livekit-server      livekit/livekit-server:latest  Up X seconds (healthy)
livekit-egress      livekit/egress:latest          Up X seconds (healthy)
livekit-nginx       nginx:alpine                   Up X seconds (healthy)
```

---

### Step 5: Test Webhook Endpoint

**From your local machine** (NOT from server):

```bash
curl https://itqan-platform.test/webhooks/livekit/health
```

**Expected output**:
```json
{
  "status": "ok",
  "timestamp": "2025-12-01T12:34:56.000000Z"
}
```

**If you get an error**:
- Check Laravel logs: `tail -50 ~/web/itqan-platform/storage/logs/laravel.log`
- Check route exists: `php artisan route:list | grep webhooks`
- Verify .env has correct LiveKit credentials

---

### Step 6: Create Test Recording

#### 6.1 Create or Find Test Session

In your Laravel app:
```bash
cd ~/web/itqan-platform
php artisan tinker
```

```php
// Find an Interactive Course with recording enabled
$course = \App\Models\InteractiveCourse::where('recording_enabled', true)->first();

// If no course has recording enabled, enable it
if (!$course) {
    $course = \App\Models\InteractiveCourse::first();
    $course->update(['recording_enabled' => true]);
}

// Find or create a session
$session = $course->sessions()
    ->where('status', 'scheduled')
    ->orWhere('status', 'ongoing')
    ->first();

// If no sessions exist, create one
if (!$session) {
    $session = \App\Models\InteractiveCourseSession::create([
        'course_id' => $course->id,
        'session_number' => 1,
        'scheduled_at' => now()->addMinutes(5),
        'duration_minutes' => 60,
        'status' => 'ready',
        'meeting_room_name' => 'test-recording-room-' . time(),
    ]);
}

echo "Session ID: {$session->id}\n";
echo "Room Name: {$session->meeting_room_name}\n";
echo "Recording Enabled: " . ($session->isRecordingEnabled() ? 'YES' : 'NO') . "\n";
echo "Can Be Recorded: " . ($session->canBeRecorded() ? 'YES' : 'NO') . "\n";
```

#### 6.2 Start Recording via API

**From your local machine**:

```bash
# Get authentication token first
# (You'll need to be logged in as an academic teacher assigned to this course)

curl -X POST https://itqan-platform.test/api/recordings/start \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"session_id": SESSION_ID_FROM_STEP_6.1}'
```

**Expected response**:
```json
{
  "success": true,
  "message": "تم بدء التسجيل بنجاح",
  "recording_id": "egress-uuid-here",
  "recording": {
    "id": 1,
    "recordable_type": "App\\Models\\InteractiveCourseSession",
    "recordable_id": SESSION_ID,
    "recording_id": "egress-uuid",
    "status": "recording",
    "started_at": "2025-12-01T12:34:56.000000Z"
  }
}
```

#### 6.3 Check LiveKit Egress Logs

**On server**:
```bash
docker logs livekit-egress --tail 50 | grep -i "recording\|egress"
```

**Expected**:
```
INFO    starting egress    {"egressId": "egress-uuid", "type": "room_composite"}
INFO    recording started  {"room": "test-recording-room-...", "egressId": "egress-uuid"}
```

#### 6.4 Wait 30 Seconds, Then Stop Recording

```bash
curl -X POST https://itqan-platform.test/api/recordings/stop \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"session_id": SESSION_ID_FROM_STEP_6.1}'
```

**Expected response**:
```json
{
  "success": true,
  "message": "تم إيقاف التسجيل وسيتم معالجته قريباً",
  "recording": {
    "id": 1,
    "status": "processing",
    "ended_at": "2025-12-01T12:35:26.000000Z"
  }
}
```

#### 6.5 Wait for Webhook (30-60 seconds)

**Watch Laravel logs**:
```bash
tail -f ~/web/itqan-platform/storage/logs/laravel.log | grep -i "egress\|recording"
```

**Expected**:
```
[2025-12-01 12:36:00] Processing egress_ended webhook {"egressId":"egress-uuid","status":"EGRESS_COMPLETE"}
[2025-12-01 12:36:00] Recording completed successfully {"recording_id":1,"file_path":"..."}
```

#### 6.6 Verify Recording File

**On server**:
```bash
ls -lh /opt/livekit/conference.itqanway.com/recordings/
```

**Expected**:
```
total 15M
-rw-r--r-- 1 root root 15M Dec  1 12:36 interactive-session-123-2025-12-01_123456.mp4
```

#### 6.7 Check Database

**In Laravel**:
```bash
php artisan tinker
```

```php
$recording = \App\Models\SessionRecording::latest()->first();
dd([
    'id' => $recording->id,
    'status' => $recording->status,
    'file_path' => $recording->file_path,
    'file_size' => $recording->file_size,
    'duration' => $recording->duration,
    'formatted_duration' => $recording->formatted_duration,
    'formatted_size' => $recording->formatted_file_size,
    'download_url' => $recording->getDownloadUrl(),
    'stream_url' => $recording->getStreamUrl(),
]);
```

**Expected**:
```php
[
  "id" => 1
  "status" => "completed"
  "file_path" => "/recordings/interactive/2025/12/session-123/interactive-session-123-2025-12-01_123456.mp4"
  "file_size" => 15728640  // ~15MB
  "duration" => 30  // 30 seconds
  "formatted_duration" => "00:30"
  "formatted_size" => "15.00 MB"
  "download_url" => "https://itqan-platform.test/api/recordings/1/download"
  "stream_url" => "https://itqan-platform.test/api/recordings/1/stream"
]
```

---

## Troubleshooting

### Egress Container Won't Start

**Check logs**:
```bash
docker logs livekit-egress --tail 100
```

**Common issues**:
1. **Wrong API credentials**: Verify egress.yaml has same credentials as livekit.yaml
2. **Can't connect to LiveKit**: Check ws_url is `ws://127.0.0.1:7880`
3. **Redis connection failed**: Check redis address is `127.0.0.1:6379`

**Fix**: Re-run finalize-recording-setup.sh

---

### Webhook Not Received

**Test webhook manually from server**:
```bash
curl -X POST https://itqan-platform.test/webhooks/livekit \
  -H "Content-Type: application/json" \
  -d '{
    "event": "egress_ended",
    "egressInfo": {
      "egressId": "test-egress-id",
      "roomName": "test-room",
      "status": "EGRESS_COMPLETE",
      "fileResults": [{
        "filename": "test.mp4",
        "size": 1000000,
        "duration": 30
      }]
    }
  }'
```

**Check Laravel logs**:
```bash
tail -50 ~/web/itqan-platform/storage/logs/laravel.log
```

**If webhook route doesn't exist**:
```bash
cd ~/web/itqan-platform
php artisan route:list | grep webhooks
```

---

### Recording File Missing

**Check egress logs**:
```bash
docker logs livekit-egress | grep -i "error\|fail"
```

**Check file permissions**:
```bash
ls -la /opt/livekit/conference.itqanway.com/
chmod -R 755 /opt/livekit/conference.itqanway.com/recordings/
```

**Check egress config**:
```bash
grep -A 5 "file_output:" /opt/livekit/conference.itqanway.com/egress.yaml
```

---

## Summary

After completing these steps, you will have:

✅ LiveKit server configured for recording
✅ Egress service with correct credentials
✅ Webhook URL configured
✅ Test recording created and verified
✅ Recording file saved to storage
✅ Database updated with recording metadata

**Ready for production use!**

For detailed API documentation, see [RECORDING_FEATURE_COMPLETE.md](RECORDING_FEATURE_COMPLETE.md).
