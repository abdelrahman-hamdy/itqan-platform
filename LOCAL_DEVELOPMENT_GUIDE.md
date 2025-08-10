# Local Development Guide - Running Cron Jobs

## 🏠 Running Scheduled Tasks in Local Development

Since you're developing locally, here are several ways to run the automated tasks:

### Method 1: Manual Execution (Recommended for Testing)

Run individual commands manually when you want to test them:

```bash
# Test the cron jobs first
php artisan test:cron-jobs --verbose

# Run individual commands
php artisan sessions:prepare
php artisan sessions:generate --weeks=2
php artisan tokens:cleanup

# Run all scheduled tasks at once
php artisan schedule:run
```

### Method 2: Schedule Daemon (Background)

Run the Laravel scheduler in the background:

```bash
# Terminal 1: Keep this running for queue processing
php artisan queue:work

# Terminal 2: Keep this running for scheduled tasks
php artisan schedule:work
```

### Method 3: Using a Simple Loop Script

Create a simple bash script to run the scheduler every minute:

```bash
# Create the script
cat > run_scheduler.sh << 'EOF'
#!/bin/bash
echo "Starting Laravel Scheduler..."
while true; do
    php artisan schedule:run >> /dev/null 2>&1
    sleep 60
done
EOF

# Make it executable
chmod +x run_scheduler.sh

# Run it
./run_scheduler.sh &
```

### Method 4: Using macOS/Linux Cron (if you want to simulate production)

```bash
# Open crontab
crontab -e

# Add this line (replace with your actual path)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

# Or for more detailed logging
* * * * * cd /path/to/your/project && php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

## 🧪 Testing the System Locally

### 1. Test Migrations
```bash
php artisan migrate:status
```

### 2. Test Cron Jobs
```bash
php artisan test:cron-jobs --verbose
```

### 3. Test Queue Processing
```bash
# Terminal 1: Start queue worker
php artisan queue:work

# Terminal 2: Test job dispatch
php artisan sessions:prepare --queue
```

### 4. Test Calendar Access
```bash
# Start development server if not running
php artisan serve

# Visit these URLs:
# http://localhost:8000/admin/google-settings
# http://localhost:8000/calendar
```

### 5. Create Test Data

Create some test sessions to see the system in action:

```bash
php artisan tinker
```

In tinker:
```php
// Create a test Quran subscription with sessions
$teacher = App\Models\QuranTeacherProfile::first();
$student = App\Models\StudentProfile::first();

if ($teacher && $student) {
    $subscription = App\Models\QuranSubscription::create([
        'academy_id' => 1,
        'student_id' => $student->id,
        'quran_teacher_id' => $teacher->id,
        'package_id' => 1, // Adjust if needed
        'status' => 'active',
        'start_date' => now(),
        'sessions_remaining' => 10,
    ]);

    // Create a test session
    App\Models\QuranSession::create([
        'academy_id' => 1,
        'quran_subscription_id' => $subscription->id,
        'scheduled_at' => now()->addHour(),
        'duration_minutes' => 60,
        'status' => 'scheduled',
        'session_type' => 'regular',
    ]);
    
    echo "Test data created!";
}
```

## 🔍 Monitoring & Debugging

### Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Scheduler specific logs
tail -f storage/logs/laravel.log | grep -i "schedule\|cron\|job"
```

### Check Queue Status
```bash
# List all jobs in queue
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Check Database
```bash
php artisan tinker
```

In tinker:
```php
// Check recent sessions
App\Models\QuranSession::latest()->take(5)->get(['id', 'scheduled_at', 'status', 'meeting_link']);

// Check Google tokens
App\Models\GoogleToken::all(['user_id', 'expires_at']);

// Check Google settings
App\Models\AcademyGoogleSettings::all(['academy_id', 'is_configured']);
```

## 🚀 Quick Start Workflow

1. **Start the basics:**
   ```bash
   php artisan serve &
   php artisan queue:work &
   ```

2. **Test the system:**
   ```bash
   php artisan test:cron-jobs
   ```

3. **Access admin panel:**
   ```
   http://localhost:8000/admin/google-settings
   ```

4. **Configure Google Settings** (see main deployment guide)

5. **Test calendar:**
   ```
   http://localhost:8000/calendar
   ```

6. **Run scheduler when needed:**
   ```bash
   php artisan schedule:run
   ```

This approach gives you full control over when tasks run, which is perfect for development and testing!