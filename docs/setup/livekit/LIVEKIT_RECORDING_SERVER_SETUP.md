# LiveKit Recording Server Setup Guide

## Overview

This guide provides step-by-step instructions to add LiveKit Egress recording service to your existing self-hosted LiveKit server at `31.97.126.52`.

**Important**: These instructions build upon your current server configuration and will NOT break existing meetings.

## Prerequisites

- ✅ LiveKit server running at `31.97.126.52:7880` (HTTP API) and `:443` (WSS)
- ✅ Docker Compose managing LiveKit services
- ✅ Nginx reverse proxy with SSL certificates
- ✅ Redis running on same Docker network
- ✅ SSH access to server as root

## Current Server Architecture

```
[Nginx :443 (SSL)] → [LiveKit :7880]
                  ↓
             [Redis :6379]
                  ↓
          [Laravel App (webhooks via ngrok)]
```

## What We'll Add

```
[Nginx :443 (SSL)] → [LiveKit :7880]
                  ↓
             [Redis :6379]  ←──┐
                  ↓            │
          [Laravel App]        │
                               │
                    [LiveKit Egress Service] → [/recordings storage]
```

---

## Step 1: SSH to Server

```bash
ssh root@31.97.126.52
cd /opt/livekit
```

**Verify current setup**:
```bash
# Check LiveKit is running
docker ps | grep livekit-server

# Check Redis is running
docker ps | grep redis

# Check nginx is running
docker ps | grep nginx
```

You should see all three containers running.

---

## Step 2: Create Egress Configuration File

Create the Egress configuration file:

```bash
nano /opt/livekit/livekit-egress.yaml
```

Paste this configuration (update `api_key` and `api_secret` to match your LiveKit server):

```yaml
# LiveKit Egress Configuration
# Server: 31.97.126.52
# Purpose: Recording interactive course sessions

# API Credentials (must match LiveKit server)
api_key: APIxdLnkvjeS3PV
api_secret: coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC

# LiveKit WebSocket URL (internal Docker network)
ws_url: ws://livekit-server:7880

# Redis Connection (same Redis as LiveKit server)
redis:
  address: redis:6379

# Local File Storage Configuration
file_output:
  local:
    enabled: true
    output_directory: /recordings

# Logging
log_level: info
log_file: /var/log/egress.log

# Performance Settings
cpu_cost:
  room_composite_cpu_cost: 3.0

# Health Check
health_port: 9090
```

Save and exit (Ctrl+X, Y, Enter).

**Verify file created**:
```bash
ls -lh /opt/livekit/livekit-egress.yaml
cat /opt/livekit/livekit-egress.yaml
```

---

## Step 3: Create Recordings Directory

Create the directory where recordings will be stored:

```bash
mkdir -p /opt/livekit/recordings
chmod 777 /opt/livekit/recordings
```

**Verify permissions**:
```bash
ls -ld /opt/livekit/recordings
# Should show: drwxrwxrwx
```

---

## Step 4: Update Docker Compose Configuration

**Backup current configuration** (important!):
```bash
cp /opt/livekit/docker-compose.yml /opt/livekit/docker-compose.yml.backup.$(date +%Y%m%d-%H%M%S)
```

**Edit Docker Compose file**:
```bash
nano /opt/livekit/docker-compose.yml
```

**Add the Egress service** to your existing `docker-compose.yml`. Add this AFTER the existing services (livekit, redis, nginx):

```yaml
  # LiveKit Egress - Recording Service
  livekit-egress:
    image: livekit/egress:latest
    container_name: livekit-egress
    restart: unless-stopped

    volumes:
      - ./livekit-egress.yaml:/etc/egress.yaml:ro
      - ./recordings:/recordings
      - /var/log/livekit:/var/log

    environment:
      - EGRESS_CONFIG_FILE=/etc/egress.yaml

    ports:
      - "9090:9090"  # Health check port

    depends_on:
      - livekit-server
      - redis

    cap_add:
      - SYS_ADMIN  # Required for Chrome browser rendering

    networks:
      - livekit-network
```

**Important Notes**:
- Replace `livekit-server` with your actual LiveKit container name (check with `docker ps`)
- Ensure `networks` section exists and references your existing network
- The `cap_add: SYS_ADMIN` is required for Chromium to render video

**If your existing docker-compose.yml doesn't have a networks section**, add this at the bottom:

```yaml
networks:
  livekit-network:
    driver: bridge
```

Save and exit (Ctrl+X, Y, Enter).

---

## Step 5: Validate Docker Compose Configuration

Before starting services, validate the configuration:

```bash
docker-compose config
```

This should output the merged configuration without errors. If you see errors, review your YAML syntax.

---

## Step 6: Deploy Egress Service

Start only the Egress service (won't restart existing containers):

```bash
docker-compose up -d livekit-egress
```

**Watch the startup logs**:
```bash
docker logs -f livekit-egress
```

**Expected output**:
```
INFO starting egress service
INFO connected to redis
INFO connected to livekit server
INFO egress service started successfully
INFO health check server listening on :9090
```

Press Ctrl+C to exit log viewing.

---

## Step 7: Verify Egress is Running

**Check container status**:
```bash
docker ps | grep egress
```

You should see:
```
CONTAINER ID   IMAGE                   STATUS          PORTS
abc123         livekit/egress:latest   Up 30 seconds   0.0.0.0:9090->9090/tcp
```

**Check health endpoint**:
```bash
curl http://localhost:9090/health
```

Expected response:
```json
{"status":"healthy"}
```

**Check Egress logs for errors**:
```bash
docker logs livekit-egress | grep -i error
```

Should return no results (no errors).

---

## Step 8: Verify Egress Can Communicate with LiveKit

Test the connection between Egress and LiveKit:

```bash
docker exec livekit-egress /bin/sh -c "wget -O- http://livekit-server:7880/health 2>/dev/null"
```

Expected response:
```json
{"status":"ok"}
```

If this fails, check your Docker network configuration.

---

## Step 9: Configure LiveKit Webhooks (If Not Already Done)

LiveKit needs to send webhooks to your Laravel application for recording events.

**Option A: Using ngrok (Local Development)**

If you're using ngrok tunnel for local development (as per previous setup):

1. Ensure ngrok is running:
   ```bash
   # On your local machine
   ngrok http https://itqan-platform.test:443 --host-header=rewrite
   ```

2. Note the ngrok URL (e.g., `https://abc123.ngrok-free.dev`)

3. LiveKit webhook configuration is handled automatically by Laravel app

**Option B: Production (Direct Server Access)**

If Laravel app is accessible directly from the LiveKit server:

Add webhook configuration to `/opt/livekit/livekit.yaml`:

```bash
nano /opt/livekit/livekit.yaml
```

Add this section:
```yaml
webhook:
  urls:
    - https://your-laravel-app.com/webhooks/livekit
  api_key: APIxdLnkvjeS3PV
```

Restart LiveKit server:
```bash
docker-compose restart livekit-server
```

---

## Step 10: Test Recording (Manual Test)

**Create a test recording** to verify everything works:

```bash
# Install LiveKit CLI (if not already installed)
wget https://github.com/livekit/livekit-cli/releases/latest/download/livekit-cli_Linux_x86_64.tar.gz
tar -xzf livekit-cli_Linux_x86_64.tar.gz
chmod +x livekit-cli
mv livekit-cli /usr/local/bin/lk

# Start a test recording
lk egress start room-composite \
  --url ws://localhost:7880 \
  --api-key APIxdLnkvjeS3PV \
  --api-secret coCkSrJcJmAKQcmODKd3qgCaa80YJSnrvGEDebrPAIJC \
  --room test-room-$(date +%s) \
  --output /recordings/test-$(date +%s).mp4
```

**Check if recording file was created**:
```bash
ls -lh /opt/livekit/recordings/
```

You should see a test MP4 file.

**Clean up test file**:
```bash
rm /opt/livekit/recordings/test-*.mp4
```

---

## Step 11: Monitor Disk Space

Since recordings will be stored locally, set up disk space monitoring:

```bash
# Check current disk usage
df -h /opt/livekit/recordings

# Create cleanup script (optional)
nano /opt/livekit/cleanup-recordings.sh
```

Add this content:
```bash
#!/bin/bash
# Delete recordings older than 30 days
find /opt/livekit/recordings -name "*.mp4" -type f -mtime +30 -delete
echo "Cleanup completed: $(date)"
```

Make executable:
```bash
chmod +x /opt/livekit/cleanup-recordings.sh
```

**Optional**: Add to cron for automatic cleanup:
```bash
crontab -e
```

Add this line (runs daily at 2 AM):
```
0 2 * * * /opt/livekit/cleanup-recordings.sh >> /var/log/recording-cleanup.log 2>&1
```

---

## Troubleshooting

### Issue 1: Egress Container Won't Start

**Symptom**: `docker ps` doesn't show `livekit-egress`

**Check logs**:
```bash
docker logs livekit-egress
```

**Common causes**:
- Missing SYS_ADMIN capability → Check `cap_add` in docker-compose.yml
- Redis connection failed → Verify Redis container name and network
- Config file not found → Check volume mount path

**Solution**:
```bash
# Restart with fresh logs
docker-compose down livekit-egress
docker-compose up -d livekit-egress
docker logs -f livekit-egress
```

### Issue 2: Health Check Fails

**Symptom**: `curl http://localhost:9090/health` returns connection refused

**Check port binding**:
```bash
docker port livekit-egress
```

Should show: `9090/tcp -> 0.0.0.0:9090`

**Check if port is in use**:
```bash
netstat -tlnp | grep 9090
```

**Solution**: Change port in docker-compose.yml if 9090 is taken.

### Issue 3: Recording Files Not Created

**Symptom**: Recording starts but no file appears in `/opt/livekit/recordings`

**Check directory permissions**:
```bash
ls -ld /opt/livekit/recordings
```

Should be `drwxrwxrwx` (777).

**Check Egress logs for Chrome errors**:
```bash
docker logs livekit-egress | grep -i chrome
```

**Solution**:
```bash
chmod 777 /opt/livekit/recordings
docker-compose restart livekit-egress
```

### Issue 4: Egress Can't Connect to LiveKit

**Symptom**: Logs show "connection refused" to LiveKit

**Verify Docker network**:
```bash
docker network ls
docker network inspect livekit-network
```

Ensure both `livekit-server` and `livekit-egress` are on same network.

**Test connectivity**:
```bash
docker exec livekit-egress ping livekit-server
```

**Solution**: Check `networks` section in docker-compose.yml.

---

## Rollback Procedure

If you need to rollback (undo changes):

```bash
# Stop Egress service
docker-compose stop livekit-egress

# Remove Egress container
docker-compose rm -f livekit-egress

# Restore previous docker-compose.yml
cp /opt/livekit/docker-compose.yml.backup.YYYYMMDD-HHMMSS /opt/livekit/docker-compose.yml

# Restart services
docker-compose up -d
```

**Note**: LiveKit server and existing meetings are NEVER affected by Egress issues.

---

## Maintenance Commands

**View Egress logs**:
```bash
docker logs livekit-egress
docker logs -f livekit-egress  # Follow logs in real-time
docker logs --tail 100 livekit-egress  # Last 100 lines
```

**Restart Egress**:
```bash
docker-compose restart livekit-egress
```

**Check disk usage**:
```bash
du -sh /opt/livekit/recordings
df -h /opt/livekit/recordings
```

**List recordings**:
```bash
ls -lh /opt/livekit/recordings
```

**Delete old recordings** (manual):
```bash
find /opt/livekit/recordings -name "*.mp4" -type f -mtime +7 -ls
# Review the list, then delete:
find /opt/livekit/recordings -name "*.mp4" -type f -mtime +7 -delete
```

---

## Security Notes

1. **File Permissions**: The recordings directory (777) is writable by all. In production, consider restricting to Docker user only.

2. **Disk Space**: Monitor `/opt/livekit/recordings` regularly. Large video files can fill disk quickly.

3. **Webhook Security**: If using direct webhook URLs (not ngrok), ensure HTTPS and consider webhook signature validation.

4. **Access Control**: Recordings will be served through Laravel. Ensure proper authentication/authorization in Laravel controllers.

---

## Next Steps

After completing this server setup:

1. ✅ Egress service running and healthy
2. ✅ Recordings directory created and writable
3. ✅ Webhooks configured (if needed)
4. ➡️ **Proceed to Laravel implementation** (see main plan document)

---

## Support & Debugging

**Check all services**:
```bash
docker ps
```

Expected output:
```
livekit-server    Up XX minutes
redis             Up XX minutes
nginx             Up XX minutes
livekit-egress    Up XX minutes  ← New
```

**View all logs**:
```bash
docker-compose logs
```

**Test end-to-end**:
1. Laravel app calls `LiveKitService::startRecording()`
2. Check Egress logs: `docker logs livekit-egress | grep "recording started"`
3. Wait for recording to finish
4. Check file created: `ls -lh /opt/livekit/recordings`
5. Laravel receives webhook: `tail -f /path/to/laravel/storage/logs/laravel.log | grep egress_ended`

---

## Summary

✅ LiveKit Egress service deployed
✅ Local file storage configured
✅ Webhooks ready (if applicable)
✅ Health checks passing
✅ Zero downtime for existing meetings
✅ Ready for Laravel integration

**Server setup complete!** Proceed with Laravel implementation steps in the main plan document.
