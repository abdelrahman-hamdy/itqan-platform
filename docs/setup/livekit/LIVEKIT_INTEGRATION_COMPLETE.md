# LiveKit Server Integration - COMPLETE ✅

## Summary

Your self-hosted LiveKit server is now fully operational and integrated with your Laravel application!

---

## Server Details

**Domain**: https://conference.itqanway.com
**WebSocket URL**: wss://conference.itqanway.com
**Server IP**: 31.97.126.52
**Location**: /opt/livekit/conference.itqanway.com

### API Credentials

```
API Key: APIDATWRbyzZbxf
API Secret: QcWYF4rTCJy9ekdsfW3bsgg1wpGUeWmsYtBIEoG12EGA
```

**⚠️ IMPORTANT**: These credentials are stored in your `.env` file and config cache.

---

## What's Been Completed

### 1. LiveKit Server Setup ✅
- ✅ Used official `livekit/generate` tool for configuration
- ✅ LiveKit server running on Docker with host networking
- ✅ Redis backend for state management
- ✅ LiveKit Egress service for recording (configured but not yet tested)
- ✅ Nginx reverse proxy with SSL termination
- ✅ Let's Encrypt SSL certificate (expires: 2026-02-28)
- ✅ Auto-renewal configured (runs every 12 hours via Certbot container)

### 2. Network Configuration ✅
- ✅ Port 80 (HTTP) - ACME challenge & redirect to HTTPS
- ✅ Port 443 (HTTPS/WSS) - LiveKit WebSocket connections
- ✅ Port 7880 (internal) - LiveKit HTTP API
- ✅ Ports 50000-60000/udp - WebRTC media streams
- ✅ UFW firewall configured
- ✅ TURN server disabled (caused port conflict, optional feature)

### 3. Laravel Integration ✅
- ✅ Environment variables updated in `.env`
- ✅ Config file (`config/livekit.php`) updated to use env() variables
- ✅ Config cache rebuilt with new credentials
- ✅ LiveKitService tested and working
- ✅ Token generation verified
- ✅ HTTPS connectivity confirmed

---

## Services Status

All Docker containers running:

```bash
# On server (31.97.126.52)
cd /opt/livekit/conference.itqanway.com
docker compose ps
```

Expected output:
```
NAME              STATUS
livekit-certbot   Up (auto-renewing SSL)
livekit-egress    Up (recording service)
livekit-nginx     Up (HTTPS proxy)
livekit-redis     Up (state management)
livekit-server    Up (main LiveKit server)
```

---

## Testing Your Integration

### Test 1: Server Connectivity

From your local machine:
```bash
curl -I https://conference.itqanway.com/
# Should return: HTTP/2 200
```

### Test 2: Laravel Integration

```bash
php artisan tinker
```

```php
// Test LiveKitService
$service = app(\App\Services\LiveKitService::class);
$service->isConfigured(); // Should return true

// Generate a test token
$user = \App\Models\User::first();
$token = $service->generateParticipantToken('test-room', $user);
echo "Token: " . substr($token, 0, 50) . "...\n"; // Should show JWT token
```

### Test 3: Create a Test Meeting

1. Go to your application
2. Navigate to an Interactive Course session
3. Click "Join Meeting" or "Start Session"
4. The LiveKit interface should load and connect to `wss://conference.itqanway.com`
5. You should be able to see your video/audio

---

## Next Steps (Recording Feature)

Your Laravel code for recording is already implemented, but you need to:

### Step 1: Configure LiveKit Webhooks

Add webhook URL to LiveKit config:

```bash
# On server
ssh root@31.97.126.52
cd /opt/livekit/conference.itqanway.com
nano livekit.yaml
```

Add this section after the `keys:` section:

```yaml
webhook:
  api_key: APIDATWRbyzZbxf  # Same as main API key
  urls:
    - https://itqan-platform.test/api/livekit/webhook  # Your Laravel app URL
```

Restart LiveKit:
```bash
docker compose restart livekit
```

### Step 2: Create Webhook Route in Laravel

The webhook endpoint should already exist at:
- Route: `POST /api/livekit/webhook`
- Controller: `LiveKitWebhookController`

It will handle these events:
- `egress_started` - Recording started
- `egress_ended` - Recording completed (file ready)
- `egress_failed` - Recording failed

### Step 3: Configure Recording Storage

Recordings are stored on the LiveKit server at:
```
/opt/livekit/conference.itqanway.com/recordings/
```

You'll need to either:
1. **Option A**: Mount this directory to your Laravel app via NFS/SSHFS
2. **Option B**: Configure a webhook to transfer files to Laravel storage after recording completes
3. **Option C**: Serve recordings directly from LiveKit server via Nginx

### Step 4: Test Recording

1. Start an Interactive Course session
2. Enable recording (if you added the UI)
3. The Egress service will create an MP4 file
4. Check the recordings directory:
   ```bash
   ssh root@31.97.126.52
   ls -lh /opt/livekit/conference.itqanway.com/recordings/
   ```

---

## Monitoring & Maintenance

### View Logs

```bash
# On server
cd /opt/livekit/conference.itqanway.com

# All services
docker compose logs -f

# Specific service
docker logs -f livekit-server
docker logs -f livekit-egress
docker logs -f livekit-nginx
```

### Restart Services

```bash
cd /opt/livekit/conference.itqanway.com

# Restart all
docker compose restart

# Restart specific service
docker compose restart livekit
docker compose restart egress
```

### Check SSL Certificate

```bash
# On server
docker exec livekit-certbot certbot certificates

# Should show:
# Certificate Name: conference.itqanway.com
# Expiry Date: 2026-02-28
```

### Update LiveKit

```bash
cd /opt/livekit/conference.itqanway.com

# Pull latest images
docker compose pull

# Recreate containers
docker compose up -d
```

---

## Troubleshooting

### Issue: "Connection refused" when joining meeting

**Check:**
1. Firewall allows port 443
2. Nginx is running: `docker ps | grep nginx`
3. LiveKit is running: `docker ps | grep livekit-server`
4. Check Nginx logs: `docker logs livekit-nginx --tail 50`

### Issue: Video/audio not working

**Check:**
1. WebRTC ports (50000-60000/udp) are open in firewall
2. STUN server is working (configured in livekit.yaml)
3. Browser console for errors
4. LiveKit logs: `docker logs livekit-server --tail 100`

### Issue: Recording not working

**Check:**
1. Egress service is running: `docker ps | grep egress`
2. Egress logs: `docker logs livekit-egress --tail 50`
3. Egress configuration: `cat egress.yaml` (verify API credentials match)
4. Recordings directory has write permissions: `ls -la recordings/`

### Issue: SSL certificate expired

**Auto-renewal should handle this**, but if needed:

```bash
cd /opt/livekit/conference.itqanway.com

# Manually renew
docker compose stop nginx
docker run --rm -it \
  --network host \
  -v $(pwd)/certbot/www:/var/www/certbot:rw \
  -v $(pwd)/certbot/conf:/etc/letsencrypt:rw \
  certbot/certbot renew
docker compose start nginx
```

---

## Important Files

### On LiveKit Server (31.97.126.52)

```
/opt/livekit/conference.itqanway.com/
├── livekit.yaml          # LiveKit server config
├── egress.yaml           # Recording service config
├── nginx.conf            # HTTPS proxy config
├── docker-compose.yaml   # All services definition
├── recordings/           # Recorded sessions (MP4 files)
├── certbot/
│   ├── conf/             # SSL certificates
│   └── www/              # ACME challenge files
└── redis-data/           # Redis persistence
```

### In Laravel App

```
.env                                          # Environment config (API credentials)
config/livekit.php                            # LiveKit configuration
app/Services/LiveKitService.php               # Main LiveKit service
app/Http/Controllers/LiveKitWebhookController.php  # Webhook handler (if exists)
app/Models/SessionRecording.php               # Recording model
database/migrations/..._create_session_recordings_table.php
```

---

## Performance Tips

1. **Memory**: LiveKit needs 1-2GB RAM. Egress needs 2-4GB for recording. Monitor with:
   ```bash
   docker stats
   ```

2. **Disk Space**: Recordings can fill up quickly. Set up auto-cleanup:
   ```bash
   # Add to cron (on server)
   0 3 * * * find /opt/livekit/conference.itqanway.com/recordings -name "*.mp4" -type f -mtime +30 -delete
   ```

3. **Network**: Monitor bandwidth during peak times. WebRTC can use 2-5 Mbps per participant.

---

## Security Checklist

- [x] SSL certificate installed and auto-renewing
- [x] UFW firewall configured
- [x] API keys stored in `.env` (not hardcoded)
- [x] HTTPS-only connections (HTTP redirects to HTTPS)
- [x] Rate limiting enabled in Nginx
- [x] TURN server disabled (optional, but was causing conflicts)
- [ ] **TODO**: Configure webhook authentication (JWT signature verification)
- [ ] **TODO**: Set up fail2ban for brute force protection (optional)

---

## Support & Documentation

- **LiveKit Docs**: https://docs.livekit.io/
- **LiveKit GitHub**: https://github.com/livekit/livekit
- **Laravel LiveKit Package**: https://github.com/agence104/livekit-server-sdk-php

---

## Quick Reference Commands

### On Server (SSH)

```bash
# Check all services
cd /opt/livekit/conference.itqanway.com && docker compose ps

# View logs
docker compose logs -f livekit

# Restart everything
docker compose restart

# Check disk space
df -h

# Check memory
free -h

# Test server
curl -I https://conference.itqanway.com/
```

### On Local (Laravel)

```bash
# Test integration
php test-livekit-simple.php

# Clear config cache
php artisan config:clear

# Rebuild config cache
php artisan config:cache

# Test in tinker
php artisan tinker
>>> app(\App\Services\LiveKitService::class)->isConfigured()
```

---

**Status**: ✅ FULLY OPERATIONAL

**Last Updated**: December 1, 2025
**LiveKit Version**: 1.9.4
**SSL Expiry**: February 28, 2026
