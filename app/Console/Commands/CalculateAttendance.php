<?php

namespace App\Console\Commands;

use App\Jobs\CalculateSessionAttendance;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Manual Attendance Calculation Command
 *
 * For admin troubleshooting and manual recalculation of attendance.
 */
class CalculateAttendance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:calculate
                          {session? : Session ID to recalculate}
                          {--type=all : Session type (quran, academic, all)}
                          {--force : Recalculate even if already calculated}
                          {--days=7 : Process sessions from last N days}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate attendance for completed sessions (manual trigger)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sessionId = $this->argument('session');
        $type = $this->option('type');
        $force = $this->option('force');
        $days = (int) $this->option('days');

        $this->info('🧮 Starting manual attendance calculation...');

        if ($sessionId) {
            // Calculate specific session
            return $this->calculateSpecificSession($sessionId, $force);
        }

        // Calculate all sessions
        return $this->calculateAllSessions($type, $force, $days);
    }

    /**
     * Calculate attendance for a specific session
     */
    private function calculateSpecificSession(int $sessionId, bool $force): int
    {
        // Try to find session in both Quran and Academic
        $session = QuranSession::find($sessionId) ?? AcademicSession::find($sessionId);

        if (! $session) {
            $this->error("❌ Session not found: {$sessionId}");
            return 1;
        }

        $this->info("📝 Processing session: {$session->id} ({$session->name})");

        $query = MeetingAttendance::where('session_id', $session->id);

        if (! $force) {
            $query->where('is_calculated', false);
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            $this->warn('⚠️  No attendance records to calculate');
            return 0;
        }

        $this->info("Found {$attendances->count()} attendance records");

        $bar = $this->output->createProgressBar($attendances->count());
        $bar->start();

        $processed = 0;
        $failed = 0;

        foreach ($attendances as $attendance) {
            try {
                $job = new CalculateSessionAttendance();
                // Use reflection to call private method (for manual command)
                $reflection = new \ReflectionClass($job);
                $method = $reflection->getMethod('calculateAttendance');
                $method->setAccessible(true);
                $method->invoke($job, $session, $attendance);

                $processed++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\n❌ Failed for user {$attendance->user_id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Calculation complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $processed],
                ['Failed', $failed],
            ]
        );

        return 0;
    }

    /**
     * Calculate attendance for all sessions
     */
    private function calculateAllSessions(string $type, bool $force, int $days): int
    {
        $this->info("📊 Processing all sessions (type: {$type}, last {$days} days)");

        if ($force) {
            $this->warn('⚠️  --force flag set: Will recalculate all attendance records');
        }

        // Dispatch the calculation job
        CalculateSessionAttendance::dispatch();

        $this->info('✅ Calculation job dispatched to queue');
        $this->comment('Monitor with: php artisan queue:work');

        return 0;
    }
}
