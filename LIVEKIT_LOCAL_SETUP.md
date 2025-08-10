# 🚀 LiveKit Local Development Setup

Complete guide to run LiveKit server locally alongside your Laravel project.

## 📋 Overview

**LiveKit Architecture:**
- **LiveKit Server** (Go application) - handles real-time video/audio
- **Laravel App** (your current app) - manages users, sessions, creates rooms
- **Frontend** (web/mobile) - connects directly to LiveKit for media

**Local Setup Benefits:**
- ✅ No external dependencies
- ✅ Full control and customization
- ✅ Free for development
- ✅ Easy to debug and test
- ✅ Simple migration to production

---

## 🐳 Quick Start (5 Minutes)

### Step 1: Start LiveKit Server
```bash
# Start the local LiveKit server and Redis
docker-compose -f docker-compose.livekit.yml up -d

# Verify it's running
docker-compose -f docker-compose.livekit.yml ps
```

### Step 2: Configure Laravel
Add to your `.env` file:
```bash
# LiveKit Local Server
LIVEKIT_SERVER_URL=http://localhost:7880
LIVEKIT_API_KEY=APIKey1
LIVEKIT_API_SECRET=ApiSecret1

# Webhook integration
LIVEKIT_WEBHOOK_SECRET=webhook_secret_key_123

# Recording (optional)
LIVEKIT_RECORDING_ENABLED=true
LIVEKIT_RECORDING_STORAGE=local
```

### Step 3: Test the Integration
```bash
# Test the commands work
php artisan meetings:create-scheduled --dry-run -v

# Test in admin panel
# Visit: http://itqan-academy.itqan-platform.test/admin/video-settings
# Click "Test Settings" button
```

**🎉 That's it! Your LiveKit is now running locally.**

---

## 🏗 What We've Set Up

### Services Running:
- **LiveKit Server**: `localhost:7880` (API)
- **Redis**: `localhost:6379` (session storage)
- **WebRTC Ports**: `50000-50100` (media streaming)

### Storage:
- **Recordings**: `storage/livekit-recordings/`
- **Configuration**: `livekit-config/livekit.yaml`
- **Data**: Docker volumes for Redis

### Integration:
- **Automatic meeting creation** every 5 minutes
- **Meeting cleanup** every 10 minutes  
- **Webhook events** sent to Laravel
- **Admin & teacher settings** panels ready

---

## 🔧 Customization

### Server Configuration
Edit `livekit-config/livekit.yaml`:
```yaml
# Room settings
room:
  max_participants: 100
  empty_timeout: 300  # 5 minutes

# Video quality
video:
  quality_profiles:
    high:
      width: 1280
      height: 720
      framerate: 30
```

### Laravel Environment
Complete `.env` options:
```bash
# === Core Settings ===
LIVEKIT_SERVER_URL=http://localhost:7880
LIVEKIT_API_KEY=APIKey1
LIVEKIT_API_SECRET=ApiSecret1

# === Features ===
LIVEKIT_RECORDING_ENABLED=true
LIVEKIT_WEBHOOKS_ENABLED=true
LIVEKIT_UI_ENABLE_CHAT=true
LIVEKIT_UI_ENABLE_SCREEN_SHARE=true

# === Performance ===
LIVEKIT_MAX_PARTICIPANTS=50
LIVEKIT_VIDEO_RESOLUTION=720p
LIVEKIT_AUDIO_BITRATE=64
LIVEKIT_VIDEO_BITRATE=1500

# === Development ===
LIVEKIT_DEBUG=true
LIVEKIT_LOG_LEVEL=info
```

---

## 🧪 Testing & Verification

### Health Check
```bash
# Test LiveKit server
curl http://localhost:7880/

# Should return: OK or server info
```

### Test Meeting Creation
```bash
# Dry run test
php artisan meetings:create-scheduled --dry-run -v

# Create actual meetings (if you have scheduled sessions)
php artisan meetings:create-scheduled -v
```

### Admin Panel Test
1. Visit: `/admin/video-settings`
2. Enable "إنشاء الاجتماعات تلقائياً"
3. Click "اختبار الإعدادات" (Test Settings)
4. Should show success message

### Teacher Panel Test
1. Visit: `/teacher-panel/{tenant}/teacher-video-settings`
2. Configure personal preferences
3. Click "اختبار الإعدادات" 
4. Should show success with teacher overrides

---

## 📊 Monitoring

### Docker Logs
```bash
# LiveKit server logs
docker-compose -f docker-compose.livekit.yml logs -f livekit

# Redis logs
docker-compose -f docker-compose.livekit.yml logs -f redis
```

### Laravel Logs
```bash
# Filter for LiveKit/meeting logs
tail -f storage/logs/laravel.log | grep -i "livekit\|meeting"
```

### System Status
```bash
# Check scheduled tasks
php artisan schedule:list

# View meeting statistics
php artisan meetings:create-scheduled --dry-run -v
```

---

## 🚀 Production Migration

When ready to deploy LiveKit separately:

### Option 1: Deploy to Server
```bash
# 1. Install LiveKit on server
wget https://github.com/livekit/livekit/releases/latest/download/livekit_linux_amd64.tar.gz

# 2. Copy config
scp livekit-config/livekit.yaml server:/etc/livekit/

# 3. Update Laravel .env
LIVEKIT_SERVER_URL=wss://livekit.yourdomain.com
```

### Option 2: Use LiveKit Cloud
```bash
# Simply change to LiveKit Cloud
LIVEKIT_SERVER_URL=wss://your-project.livekit.cloud
LIVEKIT_API_KEY=your_cloud_key
LIVEKIT_API_SECRET=your_cloud_secret
```

### Option 3: Docker Compose Production
```bash
# Use docker-compose in production
docker-compose -f docker-compose.livekit.yml up -d

# With proper SSL and domain setup
```

---

## ⚠️ Troubleshooting

### LiveKit Won't Start
```bash
# Check port conflicts
lsof -i :7880

# View startup logs
docker-compose -f docker-compose.livekit.yml logs livekit

# Common issue: Port already in use
# Solution: Change port in docker-compose.livekit.yml
```

### Laravel Can't Connect
```bash
# Test connection manually
curl -v http://localhost:7880/

# Check config cache
php artisan config:clear

# Verify environment
php artisan tinker
>>> config('livekit.server_url')
```

### Recordings Not Working
```bash
# Check storage directory exists
mkdir -p storage/livekit-recordings
chmod 755 storage/livekit-recordings

# Verify Docker volume mount
docker-compose -f docker-compose.livekit.yml down
docker-compose -f docker-compose.livekit.yml up -d
```

### WebRTC Connection Issues
- Check firewall allows UDP ports 50000-50100
- Try different network (mobile hotspot)
- Check STUN server configuration in `livekit.yaml`

---

## 🎯 Quick Commands Reference

```bash
# === Docker Management ===
docker-compose -f docker-compose.livekit.yml up -d    # Start
docker-compose -f docker-compose.livekit.yml down     # Stop
docker-compose -f docker-compose.livekit.yml logs -f  # View logs
docker-compose -f docker-compose.livekit.yml restart  # Restart

# === Laravel Testing ===
php artisan meetings:create-scheduled --dry-run -v    # Test creation
php artisan meetings:cleanup-expired --dry-run -v     # Test cleanup
php artisan schedule:work                              # Run scheduler

# === Health Checks ===
curl http://localhost:7880/                           # Test LiveKit
php artisan config:clear                              # Clear config cache
```

---

## 💡 Next Steps

After setup is complete:

1. **Test with real sessions**: Create a session in admin panel
2. **Configure teacher preferences**: Set up teacher video settings
3. **Start scheduler**: `php artisan schedule:work`
4. **Monitor logs**: Check meeting creation works
5. **Test frontend**: Connect to meeting URLs (when frontend is ready)

Your local LiveKit integration is now complete and ready for development! 🎉
