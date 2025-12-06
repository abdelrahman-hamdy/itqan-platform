# LiveKit Server Webhook Configuration Guide

## Overview
The self-hosted LiveKit server at `31.97.126.52` needs to send webhooks to your Laravel application for attendance tracking.

## Quick Setup (Local Development)

### 1. Start ngrok
```bash
# IMPORTANT: Use --host-header=rewrite to fix Valet routing
ngrok http https://itqan-platform.test:443 --host-header=rewrite
```
Copy the ngrok URL (e.g., `https://abc123xyz.ngrok-free.app`)

**Why `--host-header=rewrite`?**
Laravel Valet expects the Host header to be `itqan-platform.test`, but ngrok sends its own domain (e.g., `abc123xyz.ngrok-free.app`). This flag tells ngrok to rewrite the Host header to match the upstream server.

### 2. Configure LiveKit Server

SSH to server and edit config:
```bash
ssh root@31.97.126.52
nano /opt/livekit/livekit.yaml
```

Add webhook configuration:
```yaml
webhook:
  urls:
    - https://YOUR-NGROK-URL.ngrok-free.app/webhooks/livekit
  api_key: APIxdLnkvjeS3PV
```

Restart LiveKit:
```bash
docker-compose restart
```

### 3. Verify

Test webhook endpoint:
```bash
curl https://YOUR-NGROK-URL.ngrok-free.app/webhooks/livekit/health
```

Monitor webhooks:
```bash
tail -f storage/logs/laravel.log | grep "WEBHOOK"
```

## Multi-Academy Support

Webhooks automatically work for all academies:
- Room names contain academy info
- Session lookup finds correct academy
- Each academy's timezone used for display

## Webhook Events

- `participant_joined` → Create attendance event
- `participant_left` → Calculate duration
- `room_started` → Update session status
- `room_finished` → Mark complete

## Production Setup

For production, use your actual domain instead of ngrok:
```yaml
webhook:
  urls:
    - https://yourdomain.com/webhooks/livekit
```

