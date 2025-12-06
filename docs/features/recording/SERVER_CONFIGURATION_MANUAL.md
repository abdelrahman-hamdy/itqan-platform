# Recording Feature - Server Configuration Manual

## Status: Ready for Manual Execution

The recording feature code is **complete and tested**. This guide provides step-by-step instructions for configuring the LiveKit server.

---

## Prerequisites Verification

### ✅ Local System Status
- [x] RecordingService integrated into InteractiveCourseRecordingController
- [x] Recording routes registered ([routes/web.php:1603-1604](routes/web.php#L1603-L1604))
- [x] Webhook handler implemented ([app/Http/Controllers/LiveKitWebhookController.php:864-881](app/Http/Controllers/LiveKitWebhookController.php#L864-L881))
- [x] Webhook endpoint tested: `https://itqan-platform.test/webhooks/livekit/health` ✅
- [x] Integration tests passing (9/9)

### Server Requirements
- LiveKit server running at `31.97.126.52:7880`
- LiveKit Egress container for recording
- Access to `/opt/livekit/conference.itqanway.com/` directory
- Docker Compose v2 installed

---

## Step 1: Copy Configuration Script to Server

```bash
# From your local machine, run:
scp scripts/deployment/finalize-recording-setup.sh root@31.97.126.52:/opt/livekit/conference.itqanway.com/
```

**Alternative** (if SCP fails):
1. SSH into server: `ssh root@31.97.126.52`
2. Navigate to LiveKit directory: `cd /opt/livekit/conference.itqanway.com`
3. Create the script manually: `nano finalize-recording-setup.sh`
4. Copy content from [scripts/deployment/finalize-recording-setup.sh](../../../scripts/deployment/finalize-recording-setup.sh)
5. Save and make executable: `chmod +x finalize-recording-setup.sh`

---

## Step 2: Run the Configuration Script

SSH into the server and execute:

```bash
ssh root@31.97.126.52

cd /opt/livekit/conference.itqanway.com

# Make script executable if not already
chmod +x finalize-recording-setup.sh

# Run the setup script
bash finalize-recording-setup.sh
```

### What the Script Does

1. **Extracts API Credentials** from `livekit.yaml`
   - Reads existing `api_key` and `api_secret`
   - These credentials must match between livekit and egress

2. **Updates egress.yaml** with correct configuration:
   ```yaml
   api_key: [extracted-from-livekit.yaml]
   api_secret: [extracted-from-livekit.yaml]
   ws_url: ws://127.0.0.1:7880
   redis:
     address: 127.0.0.1:6379
   file_output:
     local:
       enabled: true
       output_directory: /recordings
   ```

3. **Configures Webhook URL** in `livekit.yaml`:
   ```yaml
   webhook:
     api_key: [your-api-key]
     urls:
       - https://itqan-platform.test/webhooks/livekit
   ```

4. **Restarts Services**:
   ```bash
   docker compose restart livekit egress
   ```

5. **Verifies Configuration**:
   - Shows container status
   - Displays egress logs
   - Shows LiveKit logs with webhook/egress entries

---

## Step 3: Verify Configuration

### 3.1 Check Container Status

```bash
cd /opt/livekit/conference.itqanway.com
docker compose ps
```

**Expected Output:**
```
NAME               STATUS          PORTS
livekit-server     Up 5 minutes    0.0.0.0:7880->7880/tcp
livekit-egress     Up 5 minutes    9090/tcp
```

Both containers should show "Up" status.

### 3.2 Check Egress Logs

```bash
docker logs livekit-egress --tail 50
```

**Look for:**
- ✅ "Connected to LiveKit server"
- ✅ "Redis connection established"
- ✅ No error messages about authentication or connection failures

**Common Issues:**
- ❌ "unauthorized" → API credentials mismatch
- ❌ "connection refused" → LiveKit server not running or wrong URL
- ❌ "redis: dial tcp" → Redis not accessible

### 3.3 Check LiveKit Logs

```bash
docker logs livekit-server --tail 50 | grep -i "webhook\|egress"
```

**Look for:**
- ✅ "Webhook configured: https://itqan-platform.test/webhooks/livekit"
- ✅ "Egress service available"

### 3.4 Test Webhook Endpoint from Server

```bash
# Test health endpoint
curl -k https://itqan-platform.test/webhooks/livekit/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "timestamp": "2025-12-01T10:07:59.777069Z",
  "service": "livekit-webhooks"
}
```

**If this fails:**
- Check DNS resolution: `nslookup itqan-platform.test`
- Check firewall: `telnet itqan-platform.test 443`
- Check SSL certificate validity

### 3.5 Verify Recording Directory

```bash
ls -la /opt/livekit/conference.itqanway.com/recordings/
```

**Expected:**
- Directory exists and is writable by Docker container
- Initially empty (recordings appear after test sessions)

---

## Step 4: Test End-to-End Recording

### 4.1 Create Test Interactive Course Session

From the Laravel application:

1. Navigate to **AcademicTeacher Panel**
2. Go to **Interactive Courses** → **Sessions**
3. Create a new test session:
   - Course: Any active course
   - Date: Today
   - Time: Current time + 5 minutes
   - Status: SCHEDULED

### 4.2 Start Recording via API

```bash
# From local machine, replace {session_id} with actual ID
curl -X POST https://itqan-platform.test/api/recordings/start \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"session_id": "{session_id}"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "تم بدء التسجيل بنجاح",
  "recording_id": "EG_xxxxxxxxxxxx",
  "recording": {
    "id": 1,
    "recording_id": "EG_xxxxxxxxxxxx",
    "status": "recording",
    "started_at": "2025-12-01 10:15:00",
    ...
  }
}
```

### 4.3 Monitor Recording

**Check application logs:**
```bash
# On local machine
php artisan pail
```

**Look for:**
- ✅ "Recording started for session {id}"
- ✅ "Egress request sent to LiveKit"

**Check LiveKit Egress logs on server:**
```bash
docker logs livekit-egress --tail 20 --follow
```

**Look for:**
- ✅ "Starting room composite recording"
- ✅ "Recording in progress"

### 4.4 Stop Recording

After 1-2 minutes:

```bash
curl -X POST https://itqan-platform.test/api/recordings/stop \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"session_id": "{session_id}"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "تم إيقاف التسجيل بنجاح"
}
```

### 4.5 Verify Webhook Received

**Check application logs** for egress_ended webhook:

```bash
php artisan pail | grep "egress_ended"
```

**Expected:**
```
Processing egress_ended webhook [egress_id: EG_xxxxxxxxxxxx]
Recording completed successfully [recording_id: 1]
File saved: /recordings/session_123_20251201_101500.mp4
```

### 4.6 Verify Recording File

**On server:**
```bash
ls -lh /opt/livekit/conference.itqanway.com/recordings/
```

**Expected:**
```
-rw-r--r-- 1 root root 15M Dec  1 10:17 session_123_20251201_101500.mp4
```

**Check database:**
```bash
php artisan tinker

>>> \App\Models\SessionRecording::latest()->first()
```

**Expected:**
- `status` = 'completed'
- `ended_at` timestamp present
- `file_path` set to recording filename
- `file_size` > 0

---

## Step 5: Production Verification Checklist

- [ ] Container status: Both livekit-server and livekit-egress running
- [ ] Egress logs: No authentication errors
- [ ] LiveKit logs: Webhook configured correctly
- [ ] Health endpoint: Returns 200 OK from server
- [ ] Recording directory: Exists and writable
- [ ] API endpoints: Start/stop recording work
- [ ] Webhook delivery: egress_ended received and processed
- [ ] File storage: Recording saved to disk
- [ ] Database records: SessionRecording created and updated
- [ ] File download: Can download recording via /recordings/{id}/download

---

## Troubleshooting

### Issue: Egress shows "unauthorized"

**Cause:** API credentials mismatch between livekit.yaml and egress.yaml

**Fix:**
```bash
# Extract credentials from livekit.yaml
cd /opt/livekit/conference.itqanway.com
grep -A 2 "keys:" livekit.yaml

# Update egress.yaml manually with same credentials
nano egress.yaml

# Restart egress
docker compose restart egress
```

### Issue: Webhook not received

**Cause:** Webhook URL not configured or unreachable

**Fix:**
```bash
# Test connectivity from server
curl -k https://itqan-platform.test/webhooks/livekit/health

# Check livekit.yaml webhook section
cat livekit.yaml | grep -A 5 "webhook:"

# If missing, add:
webhook:
  api_key: YOUR_API_KEY
  urls:
    - https://itqan-platform.test/webhooks/livekit

# Restart LiveKit
docker compose restart livekit
```

### Issue: Recording file not created

**Cause:** Recording directory permissions or egress configuration

**Fix:**
```bash
# Check directory exists and is writable
ls -la /opt/livekit/conference.itqanway.com/recordings/

# Create if missing
mkdir -p /opt/livekit/conference.itqanway.com/recordings
chmod 777 /opt/livekit/conference.itqanway.com/recordings

# Check egress.yaml output_directory setting
cat egress.yaml | grep -A 5 "file_output:"

# Verify docker-compose.yml volume mount
cat docker-compose.yml | grep -A 5 "egress:" | grep "volumes"

# Expected: ./recordings:/recordings
```

### Issue: Recording stops immediately

**Cause:** No active participants in room

**Fix:**
- Ensure at least one participant joins the session before starting recording
- Check room_name in API request matches actual LiveKit room
- Verify session meeting is created and room is active

---

## Configuration Files Reference

### livekit.yaml (excerpt)
```yaml
keys:
  YOUR_API_KEY: YOUR_API_SECRET

# Added by finalize-recording-setup.sh
webhook:
  api_key: YOUR_API_KEY
  urls:
    - https://itqan-platform.test/webhooks/livekit
```

### egress.yaml (complete)
```yaml
# LiveKit Egress Configuration
# Purpose: Recording interactive course sessions

# API Credentials (must match livekit.yaml)
api_key: YOUR_API_KEY
api_secret: YOUR_API_SECRET

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

# Performance tuning
cpu_cost:
  room_composite_cpu_cost: 3.0
```

### docker-compose.yml (egress service)
```yaml
egress:
  image: livekit/egress:latest
  container_name: livekit-egress
  network_mode: host
  volumes:
    - ./egress.yaml:/egress.yaml
    - ./recordings:/recordings
  environment:
    - EGRESS_CONFIG_FILE=/egress.yaml
  restart: unless-stopped
```

---

## Next Steps After Configuration

1. **Monitor First Week**
   - Check recording success rate
   - Monitor disk space usage
   - Review webhook delivery logs

2. **Setup Automated Cleanup** (optional)
   ```bash
   # Add to cron: delete recordings older than 30 days
   0 2 * * * find /opt/livekit/conference.itqanway.com/recordings -name "*.mp4" -mtime +30 -delete
   ```

3. **Configure Backup** (optional)
   - Setup S3 bucket for recording backups
   - Configure egress for cloud storage instead of local files

4. **Performance Tuning** (if needed)
   - Adjust `cpu_cost` in egress.yaml for server load
   - Configure recording quality settings
   - Enable video/audio only recording options

---

## Support

**Documentation:**
- [Complete Feature Guide](RECORDING_FEATURE_COMPLETE.md)
- [Implementation Details](RECORDING_IMPLEMENTATION_GAPS.md)

**Logs to Check:**
- Application: `tail -f storage/logs/laravel.log`
- LiveKit: `docker logs livekit-server --follow`
- Egress: `docker logs livekit-egress --follow`

**Database Inspection:**
```bash
php artisan tinker

# Check recent recordings
>>> \App\Models\SessionRecording::latest()->limit(5)->get()

# Check specific session recordings
>>> $session = \App\Models\InteractiveCourseSession::find(SESSION_ID);
>>> $session->recordings;
```

---

**Last Updated:** 2025-12-01
**Script Version:** finalize-recording-setup.sh v1.0
**Target Server:** 31.97.126.52 (LiveKit Production)
