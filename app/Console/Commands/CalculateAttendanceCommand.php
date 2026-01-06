<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\MeetingAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:calculate-attendance 
                          {--session-id= : Calculate attendance for specific session}
                          {--academy-id= : Process sessions for specific academy}
                          {--dry-run : Preview calculations without saving}
                          {--force : Force recalculation of already calculated attendances}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate final attendance for completed sessions using enhanced meeting tracking';

    private MeetingAttendanceService $attendanceService;

    public function __construct(MeetingAttendanceService $attendanceService)
    {
        parent::__construct();
        $this->attendanceService = $attendanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        $sessionId = $this->option('session-id');
        $academyId = $this->option('academy-id');

        $this->info('🚀 Starting attendance calculation...');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No actual changes will be made');
        }

        if ($isForced) {
            $this->warn('⚠️  FORCE MODE - Recalculating already calculated attendances');
        }

        try {
            if ($sessionId) {
                return $this->processSpecificSession($sessionId, $isDryRun, $isForced);
            } else {
                return $this->processAllEligibleSessions($academyId, $isDryRun, $isForced);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error during attendance calculation: '.$e->getMessage());
            Log::error('Attendance calculation command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process a specific session
     */
    private function processSpecificSession(int $sessionId, bool $isDryRun, bool $isForced): int
    {
        $session = QuranSession::find($sessionId);

        if (! $session) {
            $this->error("❌ Session {$sessionId} not found");

            return self::FAILURE;
        }

        $this->info("📊 Processing session {$sessionId}...");

        if ($isDryRun) {
            $this->simulateSessionCalculation($session);
        } else {
            if ($isForced) {
                $results = $this->attendanceService->recalculateAttendance($session);
            } else {
                $results = $this->attendanceService->calculateFinalAttendance($session);
            }

            $this->displaySessionResults($session, $results);
        }

        return self::SUCCESS;
    }

    /**
     * Process all eligible sessions
     */
    private function processAllEligibleSessions(?int $academyId, bool $isDryRun, bool $isForced): int
    {
        $query = QuranSession::whereIn('status', [SessionStatus::COMPLETED, SessionStatus::ABSENT])
            ->with(['academy', 'meetingAttendances']);

        if ($academyId) {
            $query->where('academy_id', $academyId);
        }

        // Get sessions that need attendance calculation
        if (! $isForced) {
            $query->whereHas('meetingAttendances', function ($attendanceQuery) {
                $attendanceQuery->where('is_calculated', false);
            });
        }

        $sessions = $query->get();

        $this->info("📊 Found {$sessions->count()} sessions to process");

        if ($sessions->isEmpty()) {
            $this->info('✅ No sessions need attendance calculation');

            return self::SUCCESS;
        }

        $results = [
            'processed_sessions' => 0,
            'total_attendances_calculated' => 0,
            'errors' => [],
        ];

        if ($isDryRun) {
            $this->simulateBulkCalculation($sessions);
        } else {
            $results = $this->attendanceService->processCompletedSessions($sessions);
        }

        $this->displayBulkResults($results);

        // Log summary
        Log::info('Attendance calculation completed', $results);

        $this->info('✅ Attendance calculation completed successfully');

        return self::SUCCESS;
    }

    /**
     * Simulate calculation for a single session
     */
    private function simulateSessionCalculation(QuranSession $session): void
    {
        $attendanceCount = $session->meetingAttendances()->count();

        $this->info("🔍 Would calculate attendance for {$attendanceCount} participants");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Session ID', $session->id],
                ['Session Status', $session->status->label()],
                ['Participants', $attendanceCount],
                ['Already Calculated', $session->meetingAttendances()->where('is_calculated', true)->count()],
                ['Needs Calculation', $session->meetingAttendances()->where('is_calculated', false)->count()],
            ]
        );
    }

    /**
     * Simulate bulk calculation
     */
    private function simulateBulkCalculation($sessions): void
    {
        $totalParticipants = 0;
        $totalSessions = $sessions->count();

        foreach ($sessions as $session) {
            $totalParticipants += $session->meetingAttendances()->count();
        }

        $this->info("🔍 Would process {$totalSessions} sessions with {$totalParticipants} total participants");

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sessions', $totalSessions],
                ['Total Participants', $totalParticipants],
                ['Estimated Processing Time', ($totalSessions * 0.5).' seconds'],
            ]
        );
    }

    /**
     * Display results for a single session
     */
    private function displaySessionResults(QuranSession $session, array $results): void
    {
        $this->info("📈 Session {$session->id} Results:");

        $this->table(
            ['Metric', 'Value'],
            [
                ['Attendances Calculated', $results['calculated_count']],
                ['Errors', count($results['errors'])],
            ]
        );

        if (! empty($results['attendances'])) {
            $this->info('👥 Participant Results:');
            $this->table(
                ['User ID', 'Status', 'Percentage', 'Duration (min)'],
                array_map(function ($attendance) {
                    return [
                        $attendance['user_id'],
                        $attendance['attendance_status'],
                        $attendance['attendance_percentage'].'%',
                        $attendance['total_duration'],
                    ];
                }, $results['attendances'])
            );
        }

        if (! empty($results['errors'])) {
            $this->error('❌ Errors occurred:');
            foreach ($results['errors'] as $error) {
                $this->error("User {$error['user_id']}: {$error['error']}");
            }
        }
    }

    /**
     * Display bulk processing results
     */
    private function displayBulkResults(array $results): void
    {
        $this->info('📈 Processing Results:');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Sessions Processed', $results['processed_sessions']],
                ['Total Attendances Calculated', $results['total_attendances_calculated']],
                ['Sessions with Errors', count($results['errors'])],
            ]
        );

        if (! empty($results['errors'])) {
            $this->error('❌ Sessions with errors:');
            foreach ($results['errors'] as $sessionId => $errors) {
                $this->error("Session {$sessionId}: ".implode(', ', $errors));
            }
        }
    }
}
