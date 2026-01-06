<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$isLocal = config('app.env') === 'local';

// LiveKit meeting management
// Create meetings for upcoming sessions (backup for observer-based creation)
$createMeetingsCommand = Schedule::command('meetings:create-scheduled')
    ->name('create-scheduled-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Create video meetings for scheduled sessions');

// Run every minute as backup (primary creation now happens via BaseSessionObserver)
$createMeetingsCommand->everyMinute();

// Cleanup expired meetings
$cleanupMeetingsCommand = Schedule::command('meetings:cleanup-expired')
    ->name('cleanup-expired-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('End expired video meetings and cleanup resources');

if ($isLocal) {
    $cleanupMeetingsCommand->everyThreeMinutes();
} else {
    $cleanupMeetingsCommand->everyTenMinutes();
}

// Update session statuses based on time
// CRITICAL: Run every minute for immediate status updates (triggers BaseSessionObserver for meeting creation)
$updateStatusesCommand = Schedule::command('sessions:update-statuses')
    ->name('update-session-statuses')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Update session statuses based on current time and business rules');

// Run every minute for near-instant status updates
$updateStatusesCommand->everyMinute();

// Enhanced session meeting management (replaces individual commands above)
$sessionMeetingCommand = Schedule::command('sessions:manage-meetings')
    ->name('manage-session-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Comprehensive session meeting management - create, update, cleanup');

if ($isLocal) {
    $sessionMeetingCommand->everyThreeMinutes();
} else {
    $sessionMeetingCommand->everyFiveMinutes();
}

// Session meeting maintenance during off-hours
Schedule::command('sessions:manage-meetings --force')
    ->name('session-meeting-maintenance')
    ->hourly()
    ->between('0:00', '6:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Session management maintenance during off-hours');

// Academic session meeting management
$academicSessionMeetingCommand = Schedule::command('academic-sessions:manage-meetings')
    ->name('manage-academic-session-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Comprehensive academic session meeting management - create, update, cleanup');

if ($isLocal) {
    $academicSessionMeetingCommand->everyThreeMinutes();
} else {
    $academicSessionMeetingCommand->everyFiveMinutes();
}

// Academic session meeting maintenance during off-hours
Schedule::command('academic-sessions:manage-meetings --force')
    ->name('academic-session-meeting-maintenance')
    ->hourly()
    ->between('0:00', '6:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Academic session management maintenance during off-hours');

// 🔥 NEW: Reconcile orphaned attendance events (missed webhooks)
Schedule::job(new \App\Jobs\ReconcileOrphanedAttendanceEvents)
    ->hourly()
    ->withoutOverlapping()
    ->description('Close attendance events orphaned by missed webhooks');

// 🎯 NEW: Calculate attendance for completed sessions (post-meeting)
$calculateAttendanceJob = Schedule::job(new \App\Jobs\CalculateSessionAttendance)
    ->withoutOverlapping()
    ->description('Calculate final attendance from webhook events after sessions end');

// Run every 10 seconds in local for fast testing, every 5 minutes in production
if ($isLocal) {
    $calculateAttendanceJob->everyTenSeconds(); // Every 10 seconds for development testing
} else {
    $calculateAttendanceJob->everyFiveMinutes(); // Every 5 minutes for production
}

// 🎥 RECORDING: Stop recordings when session scheduled end time is reached
// Runs every minute to check for sessions with active recordings that have passed their scheduled end time
Schedule::command('recordings:stop-expired')
    ->name('stop-expired-recordings')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Stop recordings for sessions that have reached their scheduled end time');

// ════════════════════════════════════════════════════════════════
// SUBSCRIPTION RENEWAL MANAGEMENT
// ════════════════════════════════════════════════════════════════

// Process automatic subscription renewals
// Runs daily at 6:00 AM (before peak usage hours)
// Handles Quran and Academic subscriptions with auto_renew enabled
// NOTE: NO grace period - failed payments immediately expire subscription
Schedule::command('subscriptions:process-renewals')
    ->name('process-subscription-renewals')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Process automatic subscription renewals for due subscriptions');

// Send renewal reminder notifications
// Runs daily at 9:00 AM (good time for users to see notifications)
// Sends 7-day and 3-day reminders before renewal date
Schedule::command('subscriptions:send-reminders')
    ->name('send-renewal-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Send renewal reminder notifications for upcoming renewals');

// Check expiring subscriptions and send notifications
// Runs daily at 9:00 AM (same time as renewal reminders)
// Sends notifications for subscriptions expiring in 7, 3, and 1 days
Schedule::command('subscriptions:check-expiring')
    ->name('check-expiring-subscriptions')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Send notifications for subscriptions expiring soon (7, 3, 1 days)');

// ════════════════════════════════════════════════════════════════
// TRIAL SESSION REMINDERS
// ════════════════════════════════════════════════════════════════

// Send trial session reminder notifications
// Runs hourly to catch sessions starting in about 1 hour
// Notifies both student and teacher before trial session
Schedule::command('trials:send-reminders')
    ->name('send-trial-reminders')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Send reminder notifications for trial sessions starting in 1 hour');

// ════════════════════════════════════════════════════════════════
// QUIZ DEADLINE REMINDERS
// ════════════════════════════════════════════════════════════════

// Send quiz deadline reminder notifications
// Runs every 30 minutes to catch quizzes expiring in 24h or 1h
// Notifies students and parents before quiz deadline
Schedule::command('quizzes:send-deadline-reminders')
    ->name('send-quiz-deadline-reminders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Send reminder notifications for quiz deadlines (24h and 1h before)');

// ════════════════════════════════════════════════════════════════
// EARNINGS CALCULATION (BACKUP)
// ════════════════════════════════════════════════════════════════

// Calculate missed earnings for completed sessions
// Runs weekly as backup (primary calculation happens via observer on session completion)
// Catches any sessions that missed earnings calculation due to queue failures
Schedule::command('earnings:calculate-missed --days=7')
    ->name('calculate-missed-earnings')
    ->weeklyOn(1, '04:00')  // Every Monday at 4:00 AM
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Backup: Calculate earnings for sessions that missed observer dispatch');

// ════════════════════════════════════════════════════════════════
// DATA MAINTENANCE
// ════════════════════════════════════════════════════════════════

// Cleanup old soft-deleted data
// Runs weekly on Sunday at 2:00 AM (low traffic period)
// Permanently deletes old soft-deleted subscriptions, sessions, and attendance events
Schedule::command('data:cleanup-soft-deleted --force')
    ->name('cleanup-soft-deleted-data')
    ->weeklyOn(0, '02:00')  // Every Sunday at 2:00 AM
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Permanently delete old soft-deleted records to prevent database bloat');

// Validate data integrity
// Runs daily at 3:00 AM
// Reports inconsistencies for manual review (does NOT auto-fix)
Schedule::command('data:validate-integrity')
    ->name('validate-data-integrity')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Validate data integrity and report inconsistencies');

// ════════════════════════════════════════════════════════════════
// HEALTH MONITORING (Spatie Health)
// ════════════════════════════════════════════════════════════════

// Send heartbeat for ScheduleCheck to verify scheduler is running
// Required for the ScheduleCheck health check to work properly
Schedule::command(\Spatie\Health\Commands\ScheduleCheckHeartbeatCommand::class)
    ->everyMinute()
    ->description('Send heartbeat for health check scheduler monitoring');

// Run health checks and store results in database
// Runs every minute for real-time monitoring in admin dashboard
Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class)
    ->everyMinute()
    ->description('Run all health checks and store results');
