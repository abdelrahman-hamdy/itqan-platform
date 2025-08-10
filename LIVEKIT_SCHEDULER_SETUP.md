# LiveKit Meeting Scheduler Setup Guide

This guide explains how to set up automated meeting creation and cleanup for your LiveKit integration.

## 📋 **Overview**

The system includes two automated processes:
- **Meeting Creation**: Automatically creates video meetings for scheduled sessions
- **Meeting Cleanup**: Automatically ends expired meetings to free up resources

## 🚀 **Quick Setup for Local Development**

### Option 1: Manual Testing (Recommended for Development)

Test the commands manually to ensure they work:

```bash
# Test meeting creation (dry run)
php artisan meetings:create-scheduled --dry-run --verbose

# Test meeting cleanup (dry run)
php artisan meetings:cleanup-expired --dry-run --verbose

# Run actual meeting creation
php artisan meetings:create-scheduled --verbose

# Run actual meeting cleanup
php artisan meetings:cleanup-expired --verbose
```

### Option 2: Laravel Scheduler (Background Process)

For continuous operation during development, run the Laravel scheduler:

```bash
# Run the scheduler once (processes all scheduled tasks)
php artisan schedule:run

# Run the scheduler in a loop (checks every minute)
php artisan schedule:work
```

### Option 3: Set up Cron Job (Production-like)

Add this single cron entry (runs every minute):

```bash
# Open crontab
crontab -e

# Add this line (replace /path/to/project with your actual path)
* * * * * cd /Users/abdelrahmanhamdy/web/itqan-platform && php artisan schedule:run >> /dev/null 2>&1
```

## 🎯 **Scheduled Tasks**

The system automatically runs these tasks:

| Task | Frequency | Description |
|------|-----------|-------------|
| `meetings:create-scheduled` | Every 5 minutes | Creates meetings for upcoming sessions |
| `meetings:cleanup-expired` | Every 10 minutes | Ends expired meetings |

## ⚙️ **Configuration Requirements**

Before running the scheduler, ensure:

### 1. LiveKit Configuration
Add to your `.env` file:
```bash
LIVEKIT_SERVER_URL=wss://your-livekit-server.com
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_api_secret
```

### 2. Video Settings Configured
- Access admin panel: `/admin/video-settings`
- Enable "إنشاء الاجتماعات تلقائياً"
- Set creation timing (default: 30 minutes before session)

### 3. Database Migration
Ensure you've run:
```bash
php artisan migrate
```

## 🧪 **Testing the Setup**

### Step 1: Test Commands Individually
```bash
# Check if commands are available
php artisan list | grep meetings

# Test creation command
php artisan meetings:create-scheduled --dry-run --verbose

# Test cleanup command  
php artisan meetings:cleanup-expired --dry-run --verbose
```

### Step 2: Test Scheduled Tasks
```bash
# List scheduled tasks
php artisan schedule:list

# Run scheduler once to test
php artisan schedule:run --verbose
```

### Step 3: Test with Real Sessions
1. Create a test session in the admin panel
2. Schedule it for ~30 minutes from now
3. Wait for auto-creation or run manually:
   ```bash
   php artisan meetings:create-scheduled --verbose
   ```

## 🔍 **Monitoring & Logs**

### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Filter for LiveKit/meeting logs
tail -f storage/logs/laravel.log | grep -i "meeting\|livekit"
```

### Command Output
Both commands provide detailed output:
- Session counts processed
- Meetings created/ended
- Error details
- Performance statistics

### Example Success Output
```
🎥 Starting automatic meeting creation process...
📅 Current time: 2025-08-10 14:30:00
🌍 Processing all active academies...
📊 Overall Results:
  • Academies processed: 1
  • Total sessions processed: 3
  • Total meetings created: 3
  • Total meetings failed: 0
✅ Meeting creation process completed successfully
```

## ⚠️ **Troubleshooting**

### Common Issues

#### 1. "LiveKit is not properly configured"
- Check `.env` file has LiveKit credentials
- Verify credentials are valid
- Test connection manually

#### 2. "No sessions found for processing"
- Ensure sessions are scheduled in the future
- Check academy has auto-creation enabled
- Verify timing settings (creation window)

#### 3. "Command not found"
- Run `composer dump-autoload`
- Clear cache: `php artisan cache:clear`

#### 4. Scheduler Not Running
- Check cron is active: `crontab -l`
- Verify file permissions
- Check PHP path in cron entry

### Debug Mode
Add `--verbose` flag to any command for detailed output:
```bash
php artisan meetings:create-scheduled --verbose
php artisan meetings:cleanup-expired --verbose
```

## 🚦 **Production Deployment**

### 1. Server Cron Setup
Add to server crontab:
```bash
* * * * * cd /var/www/your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Process Monitoring
Use a process monitor like Supervisor to ensure the scheduler stays running:

```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=php /var/www/your-project/artisan schedule:work
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/your-project/storage/logs/scheduler.log
```

### 3. Health Checks
Set up monitoring to alert if:
- Scheduler hasn't run in 10+ minutes
- High failure rates in meeting creation
- LiveKit connection issues

## 📊 **Performance Considerations**

- **Meeting Creation**: Runs every 5 minutes, processes ~30 minute window
- **Meeting Cleanup**: Runs every 10 minutes, minimal resource usage
- **Database Impact**: Lightweight queries, minimal load
- **LiveKit API**: Rate-limited, respects academy limits

## 🔄 **Manual Operations**

### Force Create Meetings Now
```bash
# Create all eligible meetings immediately
php artisan meetings:create-scheduled

# Create meetings for specific academy
php artisan meetings:create-scheduled --academy-id=1
```

### Force Cleanup Now
```bash
# End all expired meetings immediately
php artisan meetings:cleanup-expired
```

### View Statistics
Both commands show statistics at the end:
- Total auto-generated meetings
- Active meetings count
- Daily/weekly creation counts
- System health metrics

---

**🎯 Ready to Start?**

1. Run: `php artisan meetings:create-scheduled --dry-run --verbose`
2. If successful, start the scheduler: `php artisan schedule:work`
3. Monitor logs: `tail -f storage/logs/laravel.log`

Your LiveKit meeting automation is now active! 🚀
