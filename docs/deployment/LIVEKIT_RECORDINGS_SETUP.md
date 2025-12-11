# LiveKit Recordings Setup Guide

This guide explains how to configure the LiveKit server to store and serve session recordings.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Recording Flow                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Teacher clicks "Start Recording" in session UI                  │
│     ↓                                                                │
│  2. Laravel app calls LiveKit Egress API to start room recording    │
│     ↓                                                                │
│  3. LiveKit Egress records the session to /recordings/ directory    │
│     ↓                                                                │
│  4. When recording stops, Egress sends webhook to Laravel app       │
│     ↓                                                                │
│  5. Laravel updates SessionRecording with file path and metadata    │
│     ↓                                                                │
│  6. Students/Teachers access recordings via nginx on LiveKit server │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

## Server Requirements

- LiveKit Server: `conference.itqanway.com` (31.97.126.52)
- Egress service running alongside LiveKit
- Nginx for serving static recording files
- SSL certificate (Let's Encrypt recommended)

## Step 1: Configure Egress Output Directory

Your `egress.yaml` should have the file output configured:

```yaml
api_key: your_api_key
api_secret: your_api_secret
ws_url: wss://conference.itqanway.com

# Recording output directory
file_output:
  local:
    - /recordings

# Optional: S3 output for backup
# s3:
#   access_key: xxx
#   secret: xxx
#   bucket: your-bucket
#   region: me-south-1
```

Ensure the `/recordings` directory exists and is writable:

```bash
sudo mkdir -p /recordings
sudo chown -R livekit:livekit /recordings  # Adjust user as needed
sudo chmod 755 /recordings
```

## Step 2: Configure LiveKit Webhooks

Edit your `livekit.yaml` to send webhooks to the Laravel app:

```yaml
# Add or update the webhook section
webhook:
  urls:
    - https://yourdomain.itqanway.com/api/livekit/webhook
  api_key: your_api_key
```

The Laravel app will receive `egress_ended` events when recordings complete.

## Step 3: Deploy Nginx Configuration

Copy the nginx configuration to your LiveKit server:

```bash
# On your LiveKit server (31.97.126.52)
sudo nano /etc/nginx/sites-available/conference.itqanway.com
```

Add the recordings location block (see `livekit-recordings-nginx.conf`):

```nginx
location /recordings/ {
    alias /recordings/;
    autoindex off;

    # CORS for your app domain
    add_header 'Access-Control-Allow-Origin' 'https://itqanway.com' always;
    add_header 'Access-Control-Allow-Methods' 'GET, HEAD, OPTIONS' always;

    # Enable byte-range for video seeking
    add_header Accept-Ranges bytes;

    # Cache recordings
    expires 7d;
}
```

Enable and reload nginx:

```bash
sudo ln -sf /etc/nginx/sites-available/conference.itqanway.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Step 4: Laravel Environment Configuration

Add these variables to your `.env` file:

```env
# LiveKit Recording Configuration
LIVEKIT_RECORDINGS_URL=https://conference.itqanway.com/recordings
LIVEKIT_RECORDINGS_PATH=/recordings
LIVEKIT_RECORDINGS_ACCESS_MODE=redirect
```

The `access_mode` options:
- `redirect`: Redirects users directly to the recording URL (faster, uses LiveKit server bandwidth)
- `proxy`: Streams through Laravel (more control, uses app server bandwidth)

## Step 5: Verify Webhook Endpoint

The webhook endpoint should be accessible at:
```
POST https://yourdomain.itqanway.com/api/livekit/webhook
```

The `LiveKitWebhookController` handles:
- `egress_started`: Recording started
- `egress_updated`: Recording progress updates
- `egress_ended`: Recording completed with file path

## Testing the Setup

### 1. Test Nginx Recordings Access

```bash
# On LiveKit server, create a test file
echo "test" | sudo tee /recordings/test.txt

# From your local machine
curl -I https://conference.itqanway.com/recordings/test.txt
# Should return 200 OK
```

### 2. Test Recording Flow

1. Start an interactive course session
2. As teacher, click "Start Recording" button
3. Record for a few seconds
4. Click "Stop Recording"
5. Check `/recordings/` directory on LiveKit server for new file
6. Verify recording appears in session UI

### 3. Check Laravel Logs

```bash
# Monitor webhook events
tail -f storage/logs/laravel.log | grep -i "livekit\|egress\|recording"
```

## Troubleshooting

### Recording Not Starting

1. Check Egress service is running:
   ```bash
   docker ps | grep egress
   ```

2. Verify API credentials match between Egress and Laravel:
   ```bash
   # egress.yaml
   api_key: your_key
   api_secret: your_secret

   # .env
   LIVEKIT_API_KEY=your_key
   LIVEKIT_API_SECRET=your_secret
   ```

### Recording File Not Accessible

1. Check file permissions:
   ```bash
   ls -la /recordings/
   ```

2. Check nginx error logs:
   ```bash
   tail -f /var/log/nginx/recordings_error.log
   ```

3. Test direct nginx access:
   ```bash
   curl -I https://conference.itqanway.com/recordings/your-file.mp4
   ```

### Webhook Not Received

1. Verify webhook URL in `livekit.yaml`
2. Check Laravel route is accessible (no auth middleware on webhook)
3. Check nginx/firewall allows POST to webhook URL

### CORS Errors in Browser

Update nginx CORS headers to include your specific domain:
```nginx
add_header 'Access-Control-Allow-Origin' 'https://your-academy.itqanway.com' always;
```

## File Naming Convention

Recordings are saved with the format:
```
/recordings/{room_name}_{egress_id}.mp4
```

Example:
```
/recordings/interactive-session-123_EG_abc123def456.mp4
```

## Storage Management

Monitor disk usage on the LiveKit server:

```bash
# Check recordings size
du -sh /recordings/

# Find old recordings (older than 30 days)
find /recordings -type f -mtime +30 -name "*.mp4"

# Optional: Auto-cleanup old recordings (add to crontab)
# 0 2 * * * find /recordings -type f -mtime +90 -delete
```

## Security Considerations

1. **Rate Limiting**: Consider adding nginx rate limiting to prevent abuse
2. **IP Restrictions**: Optionally restrict access to known IP ranges
3. **Signed URLs**: For production, implement signed URLs with expiration
4. **Backup**: Configure S3 backup in egress.yaml for redundancy

## Related Files

- `config/livekit.php` - Laravel LiveKit configuration
- `app/Services/RecordingService.php` - Recording business logic
- `app/Http/Controllers/LiveKitWebhookController.php` - Webhook handler
- `app/Models/SessionRecording.php` - Recording model
- `resources/views/components/recordings/` - Recording UI components
