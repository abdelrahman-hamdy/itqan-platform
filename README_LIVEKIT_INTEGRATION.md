# 🚀 Complete LiveKit Integration for Itqan Platform

## 📋 Overview

This is a **complete, production-ready LiveKit integration** that runs locally alongside your Laravel project and can be easily deployed to production.

### What's Included

✅ **Local LiveKit Server** - Full video/audio streaming server  
✅ **Auto-Meeting Creation** - Automatically creates meetings before sessions  
✅ **Admin & Teacher Settings** - Complete Filament interfaces  
✅ **Automated Scheduling** - Cron jobs for meeting management  
✅ **Recording Support** - Local storage with S3/GCS options  
✅ **Webhook Integration** - Real-time event processing  
✅ **Easy Deployment** - Docker-based with production migration path  

---

## 🏃‍♂️ Quick Start (5 Minutes)

### Step 1: Start LiveKit Server
```bash
# Make sure Docker Desktop is running, then:
./start-livekit.sh
```

### Step 2: Add Environment Variables
Add to your `.env` file:
```bash
# LiveKit Local Server
LIVEKIT_SERVER_URL=http://localhost:7880
LIVEKIT_API_KEY=APIKey1
LIVEKIT_API_SECRET=ApiSecret1
LIVEKIT_WEBHOOK_SECRET=webhook_secret_key_123
LIVEKIT_RECORDING_ENABLED=true
```

### Step 3: Start Laravel Scheduler
```bash
# In a new terminal, start the meeting automation
php artisan schedule:work
```

### Step 4: Test the Integration
```bash
# Test meeting creation
php artisan meetings:create-scheduled --dry-run -v

# Visit admin panel and test settings
# http://itqan-academy.itqan-platform.test/admin/video-settings
```

**🎉 That's it! Your LiveKit integration is running locally.**

---

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel App   │    │  LiveKit Server │    │      Redis      │
│  (Your Project) │◄──►│   (localhost)   │◄──►│   (Sessions)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│     Database    │    │   Recordings    │
│   (Sessions)    │    │    (Storage)    │
└─────────────────┘    └─────────────────┘
```

### Key Components

1. **LiveKit Server** (Port 7880)
   - Handles real-time video/audio streaming
   - Manages meeting rooms and participants
   - Processes recordings

2. **Laravel Integration**
   - Manages user authentication and sessions
   - Creates/destroys meeting rooms via API
   - Handles webhooks and events

3. **Redis** (Port 6379)
   - Session storage for LiveKit
   - Caching for performance

4. **Storage**
   - Recordings: `storage/livekit-recordings/`
   - Configuration: `livekit-config/`

---

## 🎛️ Configuration

### Admin Panel Settings
**URL**: `/admin/video-settings`

**Key Settings**:
- ✅ **Auto-create meetings**: Enable automatic meeting creation
- ⏰ **Creation timing**: 30 minutes before session (default)
- 🎥 **Video quality**: High/Medium/Low
- 📹 **Recording**: Enable/disable by default
- 🔧 **Advanced**: Participant limits, timeouts, features

### Teacher Panel Settings  
**URL**: `/teacher-panel/{tenant}/teacher-video-settings`

**Personal Overrides**:
- 🎛️ **Quality preferences**: Override academy defaults
- 👥 **Student permissions**: Camera, microphone, screen sharing
- 📅 **Availability**: Personal schedule restrictions
- 📊 **Recording preferences**: Auto-record, quality, storage

---

## 🤖 Automated Features

### Meeting Lifecycle
1. **Creation**: Auto-created 30 minutes before session
2. **Management**: Participant permissions applied
3. **Recording**: Started/stopped based on settings  
4. **Cleanup**: Expired meetings ended automatically

### Scheduled Tasks
```bash
# View scheduled tasks
php artisan schedule:list

# Runs every 5 minutes
meetings:create-scheduled

# Runs every 10 minutes  
meetings:cleanup-expired
```

### Webhook Events
LiveKit sends real-time events to Laravel:
- Room started/finished
- Participant joined/left
- Recording started/finished

---

## 🧪 Testing

### Basic Connectivity
```bash
# Test LiveKit server
curl http://localhost:7880/

# Test Redis
docker exec itqan-livekit-redis redis-cli ping

# Test Laravel integration
php artisan meetings:create-scheduled --dry-run -v
```

### Admin Panel Tests
1. Visit `/admin/video-settings`
2. Configure settings
3. Click "Test Settings" - should show success
4. Create a test session for future time
5. Wait for auto-creation or run manually

### Teacher Panel Tests
1. Visit `/teacher-panel/{tenant}/teacher-video-settings`
2. Configure personal preferences
3. Click "Test Settings" - should show teacher overrides
4. Test availability and permissions

---

## 📊 Monitoring

### View Logs
```bash
# LiveKit server logs
docker-compose -f docker-compose.livekit.yml logs -f livekit

# Laravel meeting logs
tail -f storage/logs/laravel.log | grep -i "meeting\|livekit"

# Redis logs
docker-compose -f docker-compose.livekit.yml logs -f redis
```

### Health Checks
```bash
# System status
php artisan meetings:create-scheduled --dry-run -v

# Docker services
docker-compose -f docker-compose.livekit.yml ps

# Database connections
php artisan tinker
>>> \App\Models\VideoSettings::forAcademy(\App\Models\Academy::first())->testConfiguration()
```

---

## 🚀 Production Deployment

### Option 1: Separate LiveKit Server

**Deploy LiveKit to dedicated server:**
```bash
# 1. On server: Install LiveKit
wget https://github.com/livekit/livekit/releases/latest/download/livekit_linux_amd64.tar.gz
tar -xzf livekit_linux_amd64.tar.gz

# 2. Copy configuration
scp livekit-config/livekit.yaml server:/etc/livekit/

# 3. Update Laravel .env
LIVEKIT_SERVER_URL=wss://livekit.yourdomain.com
LIVEKIT_API_KEY=production_key
LIVEKIT_API_SECRET=production_secret

# 4. Configure SSL and domain
# Point livekit.yourdomain.com to LiveKit server
# Set up SSL certificate
```

### Option 2: LiveKit Cloud

**Use managed LiveKit Cloud:**
```bash
# Sign up at livekit.io
# Get credentials and update .env
LIVEKIT_SERVER_URL=wss://your-project.livekit.cloud
LIVEKIT_API_KEY=cloud_api_key
LIVEKIT_API_SECRET=cloud_api_secret
```

### Option 3: Docker Production

**Keep Docker setup in production:**
```bash
# Use docker-compose on production server
docker-compose -f docker-compose.livekit.yml up -d

# Configure SSL proxy (nginx/traefik)
# Point domain to Docker container
```

---

## 🛠️ Management Commands

### Daily Operations
```bash
# Start LiveKit
./start-livekit.sh

# Stop LiveKit  
./stop-livekit.sh

# Restart services
docker-compose -f docker-compose.livekit.yml restart

# View all logs
docker-compose -f docker-compose.livekit.yml logs -f
```

### Meeting Management
```bash
# Create meetings now
php artisan meetings:create-scheduled -v

# Cleanup expired meetings
php artisan meetings:cleanup-expired -v

# Start scheduler daemon
php artisan schedule:work

# Test specific academy
php artisan meetings:create-scheduled --academy-id=1 --dry-run -v
```

### Troubleshooting
```bash
# Check configuration
php artisan config:clear
php artisan meetings:create-scheduled --dry-run -v

# Reset Docker setup
docker-compose -f docker-compose.livekit.yml down -v
./start-livekit.sh

# Check port conflicts
lsof -i :7880
lsof -i :6379
```

---

## 📁 File Structure

```
itqan-platform/
├── docker-compose.livekit.yml     # LiveKit Docker setup
├── start-livekit.sh               # Easy start script
├── stop-livekit.sh                # Easy stop script
├── livekit-config/
│   └── livekit.yaml               # LiveKit server config
├── storage/
│   └── livekit-recordings/        # Recording storage
├── app/
│   ├── Services/
│   │   ├── LiveKitService.php     # LiveKit API integration
│   │   └── AutoMeetingCreationService.php
│   ├── Models/
│   │   ├── VideoSettings.php     # Academy video settings
│   │   └── TeacherVideoSettings.php
│   ├── Console/Commands/
│   │   ├── CreateScheduledMeetingsCommand.php
│   │   └── CleanupExpiredMeetingsCommand.php
│   └── Http/Controllers/
│       ├── LiveKitWebhookController.php
│       └── LiveKitMeetingController.php
└── config/
    └── livekit.php                # Laravel config
```

---

## ⚠️ Security Notes

### Development vs Production

**Development** (Current Setup):
- Uses default API keys (`APIKey1`/`ApiSecret1`)
- HTTP connections (`localhost:7880`)
- Simplified CORS settings

**Production Requirements**:
- ✅ Generate secure API keys
- ✅ Use HTTPS/WSS connections
- ✅ Configure proper CORS origins
- ✅ Set up firewall rules
- ✅ Enable webhook authentication

### Security Checklist
```bash
# Production security
- [ ] Change default API keys
- [ ] Enable HTTPS/WSS
- [ ] Configure CORS properly  
- [ ] Set webhook secrets
- [ ] Firewall UDP ports (50000-50100)
- [ ] SSL certificates
- [ ] Rate limiting
```

---

## 🎯 Next Steps

### Immediate Actions
1. **Start the system**: Run `./start-livekit.sh`
2. **Configure settings**: Visit admin panel
3. **Test integration**: Create a session and test auto-creation
4. **Monitor logs**: Check that automation works

### Future Enhancements
1. **Frontend integration**: Connect meeting rooms to React/Vue/Flutter
2. **Advanced recording**: Custom layouts and processing
3. **Analytics**: Meeting usage and quality metrics  
4. **Scalability**: Multi-region LiveKit deployment
5. **Mobile apps**: Flutter SDK integration

---

## 💡 FAQ

**Q: Do I need a separate server for LiveKit?**
A: No! This setup runs everything locally. You can migrate to a separate server later.

**Q: Can I use this in production?**  
A: Yes! Just change the server URL and API keys for production deployment.

**Q: What about Google Meet integration?**
A: It's kept separate and decoupled. Both can coexist - LiveKit for internal meetings, Google for external needs.

**Q: Does this work with mobile apps?**
A: Yes! LiveKit has Flutter/React Native SDKs that connect directly to your server.

**Q: How much does this cost?**
A: Self-hosted LiveKit is free. You only pay for server resources.

---

**🚀 Your LiveKit integration is complete and ready for production!**

Need help? Check the logs, test the endpoints, or review the configuration files. Everything is designed to work out of the box! 🎉
