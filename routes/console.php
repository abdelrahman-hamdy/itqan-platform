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
