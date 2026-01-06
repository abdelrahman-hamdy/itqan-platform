<?php

namespace App\Console\Commands;

use App\Models\MeetingAttendance;
use App\Models\MeetingAttendanceEvent;
use Illuminate\Console\Command;

/**
 * View Recent Webhook Activity (Debugging Tool)
 *
 * Shows recent webhook events and attendance records for debugging.
 */
class ViewWebhookActivity extends Command
{
    protected $signature = 'attendance:debug
                          {session? : Session ID to filter by}
                          {--events=10 : Number of recent events to show}
                          {--watch : Watch mode - refresh every 5 seconds}';

    protected $description = 'View recent webhook activity and attendance records (debugging)';

    public function handle(): int
    {
        $sessionId = $this->argument('session');
        $eventsCount = (int) $this->option('events');
        $watch = $this->option('watch');

        do {
            if ($watch) {
                $this->output->write("\033[2J\033[;H"); // Clear screen
            }

            $this->info('=== LiveKit Webhook Activity (Last 5 Minutes) ===');
            $this->newLine();

            // Show recent webhook events
            $query = MeetingAttendanceEvent::with('user:id,first_name,last_name')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderBy('event_timestamp', 'desc')
                ->limit($eventsCount);

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $events = $query->get();

            if ($events->isEmpty()) {
                $this->warn('No webhook events in the last 5 minutes');
            } else {
                $this->table(
                    ['Time', 'Event', 'Session', 'User', 'Participant SID', 'Duration'],
                    $events->map(function ($event) {
                        return [
                            $event->event_timestamp->format('H:i:s'),
                            $this->colorizeEvent($event->event_type),
                            "#{$event->session_id}",
                            $event->user ? $event->user->first_name.' '.$event->user->last_name : 'Unknown',
                            substr($event->participant_sid ?? '', 0, 12),
                            $event->duration_minutes ? "{$event->duration_minutes}min" : '-',
                        ];
                    })
                );
            }

            $this->newLine();

            // Show current attendance records
            $this->info('=== Current Attendance Records (Uncalculated) ===');
            $this->newLine();

            $attendanceQuery = MeetingAttendance::with(['user:id,first_name,last_name'])
                ->where('is_calculated', false)
                ->orderBy('updated_at', 'desc')
                ->limit(10);

            if ($sessionId) {
                $attendanceQuery->where('session_id', $sessionId);
            }

            $attendances = $attendanceQuery->get();

            if ($attendances->isEmpty()) {
                $this->warn('No uncalculated attendance records');
            } else {
                $this->table(
                    ['Session', 'User', 'First Join', 'Last Leave', 'Cycles', 'Duration'],
                    $attendances->map(function ($attendance) {
                        $cycles = is_array($attendance->join_leave_cycles) ? count($attendance->join_leave_cycles) : 0;

                        return [
                            "#{$attendance->session_id}",
                            $attendance->user ? $attendance->user->first_name.' '.$attendance->user->last_name : 'Unknown',
                            $attendance->first_join_time ? $attendance->first_join_time->format('H:i:s') : '-',
                            $attendance->last_leave_time ? $attendance->last_leave_time->format('H:i:s') : 'In meeting',
                            $cycles,
                            "{$attendance->total_duration_minutes}min",
                        ];
                    })
                );
            }

            // Show calculation job status
            $this->newLine();
            $this->info('=== System Status ===');
            $this->line('Current time: '.now()->format('Y-m-d H:i:s'));

            $interval = config('app.env') === 'local' ? '10 seconds' : '5 minutes';
            $this->line("Calculation job: Runs every {$interval}");
            $this->line('Sessions will be calculated shortly after they end');

            if ($watch) {
                $this->newLine();
                $this->comment('Refreshing in 5 seconds... (Ctrl+C to stop)');
                sleep(5);
            }

        } while ($watch);

        return 0;
    }

    private function colorizeEvent(string $eventType): string
    {
        return match ($eventType) {
            'join' => '<fg=green>✓ JOIN</>',
            'leave' => '<fg=red>✗ LEAVE</>',
            'reconnect' => '<fg=yellow>⟳ RECONNECT</>',
            default => $eventType,
        };
    }
}
