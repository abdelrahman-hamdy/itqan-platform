<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
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

// Session meeting maintenance during off-hours (00:00-06:00 UTC)
// Note: Commands have internal per-academy timezone checks for actual business hours
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

// Academic session meeting maintenance during off-hours (00:00-06:00 UTC)
// Note: Commands have internal per-academy timezone checks for actual business hours
Schedule::command('academic-sessions:manage-meetings --force')
    ->name('academic-session-meeting-maintenance')
    ->hourly()
    ->between('0:00', '6:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Academic session management maintenance during off-hours');

// Reconcile orphaned attendance events (missed webhooks)
Schedule::job(new \App\Jobs\ReconcileOrphanedAttendanceEvents)
    ->hourly()
    ->withoutOverlapping()
    ->description('Close attendance events orphaned by missed webhooks');

// Calculate attendance for completed sessions (post-meeting)
$calculateAttendanceJob = Schedule::job(new \App\Jobs\CalculateSessionAttendance)
    ->withoutOverlapping()
    ->description('Calculate final attendance from webhook events after sessions end');

// Run every 10 seconds in local for fast testing, every minute in production as safety net
// Primary calculation now happens via per-session CalculateSessionForAttendance job
if ($isLocal) {
    $calculateAttendanceJob->everyTenSeconds(); // Every 10 seconds for development testing
} else {
    $calculateAttendanceJob->everyMinute(); // Every minute as safety net (per-session job handles primary calc)
}

// RECORDING: Stop recordings when session scheduled end time is reached
// Runs every minute to check for sessions with active recordings that have passed their scheduled end time
Schedule::command('recordings:stop-expired')
    ->name('stop-expired-recordings')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Stop recordings for sessions that have reached their scheduled end time');

// RECORDING: Auto-delete recordings older than 7 days
Schedule::command('recordings:cleanup --days=7')
    ->name('cleanup-expired-recordings')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Delete session recordings older than 7 days');

// RECORDING: Process stale recording queue entries (safety net for missed webhooks)
Schedule::command('recordings:process-queue')
    ->name('process-recording-queue')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Process stale recording queue entries and promote waiting sessions');

// ════════════════════════════════════════════════════════════════
// SUBSCRIPTION MANAGEMENT
// ════════════════════════════════════════════════════════════════

// Expire active subscriptions past their end date
// Runs hourly to transition ACTIVE → EXPIRED for subscriptions where ends_at < now()
Schedule::command('subscriptions:expire-active --force')
    ->name('expire-active-subscriptions')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Pause active subscriptions past their end date (respects queued cycles + grace)');

// Advance queued subscription cycles into active state when the current cycle ends
// Runs hourly so the student never sees a gap between cycles
Schedule::command('subscriptions:advance-cycles')
    ->name('advance-subscription-cycles')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Promote queued subscription cycles to active when the current cycle ends');

// Send expiry reminders for subscriptions expiring in 7, 3, or 1 days
// Runs daily at 08:00 AM Riyadh time
Schedule::command('subscriptions:send-expiry-reminders')
    ->name('send-expiry-reminders')
    ->dailyAt('08:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Send expiry reminders for subscriptions expiring soon');

// Cancel expired pending subscriptions
// Runs every 2 hours to clean up pending subscriptions not paid within 24 hours
// Prevents users from accumulating stale pending subscriptions
Schedule::command('subscriptions:cleanup-expired-pending --force')
    ->name('cleanup-expired-pending-subscriptions')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Cancel pending subscriptions not paid within the configured time limit');

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
// SUBSCRIPTION COUNT RECONCILIATION (SAFETY NET)
// ════════════════════════════════════════════════════════════════

// Catch completed/absent sessions where subscription_counted = false
// Handles cases where the queued FinalizeAttendanceListener failed
Schedule::command('subscriptions:reconcile-missed')
    ->name('reconcile-subscription-counts')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Safety net: count subscriptions for completed sessions that were missed');

// Recalculate denormalized teacher profile counters (total_students, total_sessions)
// Required because total_students/total_sessions are excluded from $fillable for security
// and no observer/listener currently maintains them. Runs as safety net every 15 minutes.
Schedule::command('teachers:recalculate-counters')
    ->name('recalculate-teacher-counters')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Recalculate total_students/total_sessions on teacher profiles from actual session data');

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
// PAYMENT MAINTENANCE
// ════════════════════════════════════════════════════════════════

// Expire pending payments older than 24 hours
// Runs daily at 01:00 UTC (4:00 AM Asia/Riyadh, 3:00 AM Africa/Cairo)
// Note: This affects all academies regardless of their timezone setting
// Uses withoutGlobalScopes() since this runs without tenant context
Schedule::call(function () {
    // CONC-002: Only expire payments that were never attempted via gateway
    // Prevents race condition where webhook marks payment completed while this bulk update runs
    Payment::withoutGlobalScopes()
        ->where('status', PaymentStatus::PENDING)
        ->where('created_at', '<', now()->subHours(24))
        ->whereNull('gateway_transaction_id')
        ->update(['status' => PaymentStatus::EXPIRED]);
})->daily()->at('01:00')->name('expire-pending-payments')
    ->description('Expire pending payments older than 24 hours');

// Send missed payment/subscription notifications (catch webhook failures)
// Runs every 15 minutes to catch payments that succeeded but didn't receive notifications
// Handles scenarios where webhook never arrived or failed to trigger notification
Schedule::command('payments:send-missed-notifications')
    ->name('send-missed-notifications')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Send notifications for successful payments that missed webhook delivery');

// ════════════════════════════════════════════════════════════════
// EXCHANGE RATE MAINTENANCE
// ════════════════════════════════════════════════════════════════

// Refresh exchange rates daily (open.er-api.com updates daily)
// Run at 06:30 after subscription renewals (06:00) complete
Schedule::command('exchange-rates:refresh')
    ->name('refresh-exchange-rates')
    ->dailyAt('06:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Fetch latest SAR→EGP exchange rates and persist to database');

// ════════════════════════════════════════════════════════════════
// HEALTH MONITORING (Spatie Health)
// ════════════════════════════════════════════════════════════════

// Send heartbeat for ScheduleCheck to verify scheduler is running
// Required for the ScheduleCheck health check to work properly
Schedule::command(\Spatie\Health\Commands\ScheduleCheckHeartbeatCommand::class)
    ->everyMinute()
    ->description('Send heartbeat for health check scheduler monitoring');

// Run health checks and store results in database
// CFG-002: Reduced from everyMinute to everyFiveMinutes to decrease DB/Redis/disk load
Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class)
    ->everyFiveMinutes()
    ->description('Run all health checks and store results');

// ════════════════════════════════════════════════════════════════
// NOTIFICATION MAINTENANCE
// ════════════════════════════════════════════════════════════════

// Purge old read notifications (weekly, Sunday 02:00 AM)
Schedule::command('notifications:purge --days=90')
    ->name('notifications-purge')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Purge read notifications older than 90 days');

// Cleanup stale device tokens (weekly, Sunday 02:30 AM)
Schedule::command('device-tokens:cleanup --days=90')
    ->name('cleanup-stale-device-tokens')
    ->weekly()
    ->sundays()
    ->at('02:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Remove device tokens not used in 90+ days');
