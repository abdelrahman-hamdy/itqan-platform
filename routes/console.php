<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$isLocal = config('app.env') === 'local';

// LiveKit meeting management
// Create meetings for upcoming sessions
$createMeetingsCommand = Schedule::command('meetings:create-scheduled')
    ->name('create-scheduled-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Create video meetings for scheduled sessions');

if ($isLocal) {
    $createMeetingsCommand->everyMinute();
} else {
    $createMeetingsCommand->everyFiveMinutes();
}

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
$updateStatusesCommand = Schedule::command('sessions:update-statuses')
    ->name('update-session-statuses')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Update session statuses based on current time and business rules');

if ($isLocal) {
    $updateStatusesCommand->everyTwoMinutes();
} else {
    $updateStatusesCommand->everyFiveMinutes();
}

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
