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

// Set to every minute for testing regardless of environment
$createMeetingsCommand->everyMinute(); // Every minute for testing

// if ($isLocal) {
//     $createMeetingsCommand->everyMinute(); // Every minute for development
// } else {
//     $createMeetingsCommand->everyFiveMinutes(); // Every 5 minutes for production
// }

// Cleanup expired meetings
$cleanupMeetingsCommand = Schedule::command('meetings:cleanup-expired')
    ->name('cleanup-expired-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('End expired video meetings and cleanup resources');

// Set to every minute for testing regardless of environment
$cleanupMeetingsCommand->everyMinute(); // Every minute for testing

// if ($isLocal) {
//     $cleanupMeetingsCommand->everyThreeMinutes(); // Every 3 minutes for development
// } else {
//     $cleanupMeetingsCommand->everyTenMinutes(); // Every 10 minutes for production
// }

// Update session statuses based on time
$updateStatusesCommand = Schedule::command('sessions:update-statuses')
    ->name('update-session-statuses')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Update session statuses based on current time and business rules');

// Set to every minute for testing regardless of environment
$updateStatusesCommand->everyMinute(); // Every minute for testing

// if ($isLocal) {
//     $updateStatusesCommand->everyTwoMinutes(); // Every 2 minutes for development
// } else {
//     $updateStatusesCommand->everyFiveMinutes(); // Every 5 minutes for production
// }

// Enhanced session meeting management (replaces individual commands above)
$sessionMeetingCommand = Schedule::command('sessions:manage-meetings')
    ->name('manage-session-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Comprehensive session meeting management - create, update, cleanup');

// Set to every minute for testing regardless of environment
$sessionMeetingCommand->everyMinute(); // Every minute for testing

// if ($isLocal) {
//     $sessionMeetingCommand->everyThreeMinutes(); // Every 3 minutes for development
// } else {
//     $sessionMeetingCommand->everyFiveMinutes(); // Every 5 minutes for production
// }

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

// Set to every minute for testing regardless of environment
$academicSessionMeetingCommand->everyMinute(); // Every minute for testing

// if ($isLocal) {
//     $academicSessionMeetingCommand->everyThreeMinutes(); // Every 3 minutes for development
// } else {
//     $academicSessionMeetingCommand->everyFiveMinutes(); // Every 5 minutes for production
// }

// Academic session meeting maintenance during off-hours
Schedule::command('academic-sessions:manage-meetings --force')
    ->name('academic-session-meeting-maintenance')
    ->hourly()
    ->between('0:00', '6:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Academic session management maintenance during off-hours');
