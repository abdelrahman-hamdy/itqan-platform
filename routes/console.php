<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$isLocal = config('app.env') === 'local';

// Schedule group circle sessions generation
$groupSessionsCommand = Schedule::command('quran:generate-group-sessions')
    ->name('generate-group-sessions')
    ->withoutOverlapping()
    ->runInBackground();

if ($isLocal) {
    $groupSessionsCommand->everyFiveMinutes(); // Every 5 minutes for development
} else {
    $groupSessionsCommand->hourly(); // Hourly for production
}

// LiveKit meeting management
// Create meetings for upcoming sessions
$createMeetingsCommand = Schedule::command('meetings:create-scheduled')
    ->name('create-scheduled-meetings')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Create video meetings for scheduled sessions');

if ($isLocal) {
    $createMeetingsCommand->everyMinute(); // Every minute for development
} else {
    $createMeetingsCommand->everyFiveMinutes(); // Every 5 minutes for production
}

// Cleanup expired meetings
$cleanupMeetingsCommand = Schedule::command('meetings:cleanup-expired')
    ->name('cleanup-expired-meetings')  
    ->withoutOverlapping()
    ->runInBackground()
    ->description('End expired video meetings and cleanup resources');

if ($isLocal) {
    $cleanupMeetingsCommand->everyThreeMinutes(); // Every 3 minutes for development
} else {
    $cleanupMeetingsCommand->everyTenMinutes(); // Every 10 minutes for production
}
