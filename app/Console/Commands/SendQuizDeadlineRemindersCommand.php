<?php

namespace App\Console\Commands;

use Exception;
use App\Models\QuizAssignment;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SendQuizDeadlineRemindersCommand
 *
 * Sends reminder notifications for upcoming quiz deadlines.
 * Runs every 30 minutes via scheduler.
 *
 * REMINDER SCHEDULE:
 * - 24 hours before deadline: First reminder to students and parents
 * - 1 hour before deadline: Urgent reminder to students and parents
 */
class SendQuizDeadlineRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quizzes:send-deadline-reminders
                          {--dry-run : Show what would be done without actually sending}
                          {--details : Show detailed output for each reminder}';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder notifications for upcoming quiz deadlines (24h and 1h before)';

    public function __construct(
        private CronJobLogger $cronJobLogger
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('details') || $isDryRun;

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('quizzes:send-deadline-reminders', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
        ]);

        $this->info('Starting quiz deadline reminder processing...');
        $this->info('Current time: '.now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual reminders will be sent');
        }

        try {
            $results = $this->processReminders($isDryRun, $isVerbose);

            $this->displayResults($results, $isDryRun);

            // Log completion
            $this->cronJobLogger->logCronEnd('quizzes:send-deadline-reminders', $executionData, $results);

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error('Quiz deadline reminder processing failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('quizzes:send-deadline-reminders', $executionData, $e);

            Log::error('Quiz deadline reminder processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process and send quiz deadline reminders
     */
    private function processReminders(bool $isDryRun, bool $isVerbose): array
    {
        $results = [
            '24h_reminders_sent' => 0,
            '1h_reminders_sent' => 0,
            'students_notified' => 0,
            'skipped' => 0,
            'errors' => [],
            'assignments' => [],
        ];

        // Get all quiz assignments with upcoming deadlines
        $assignments = QuizAssignment::with(['quiz', 'assignable'])
            ->whereNotNull('available_until')
            ->where('is_visible', true)
            ->where('available_until', '>', now())
            ->where('available_until', '<=', now()->addHours(config('business.quiz.deadline_lookahead_hours', 25)))
            ->get();

        $this->info("Found {$assignments->count()} quiz assignments with deadlines in the next 25 hours");

        foreach ($assignments as $assignment) {
            try {
                // Check if 24h reminder should be sent
                if ($assignment->shouldSendDeadlineReminder('24h')) {
                    $this->processAssignmentReminder($assignment, '24h', $isDryRun, $isVerbose, $results);
                }

                // Check if 1h reminder should be sent
                if ($assignment->shouldSendDeadlineReminder('1h')) {
                    $this->processAssignmentReminder($assignment, '1h', $isDryRun, $isVerbose, $results);
                }

            } catch (Exception $e) {
                $results['errors'][] = [
                    'assignment_id' => $assignment->id,
                    'quiz_title' => $assignment->quiz?->title ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to process quiz deadline reminder', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage(),
                ]);

                if ($isVerbose) {
                    $this->error("  Error: {$e->getMessage()}");
                }
            }
        }

        return $results;
    }

    /**
     * Process reminder for a specific assignment
     */
    private function processAssignmentReminder(
        QuizAssignment $assignment,
        string $type,
        bool $isDryRun,
        bool $isVerbose,
        array &$results
    ): void {
        $assignmentInfo = [
            'id' => $assignment->id,
            'quiz_title' => $assignment->quiz?->title ?? 'Unknown',
            'deadline' => $assignment->available_until->format('Y-m-d H:i'),
            'type' => $type,
            'assignable_type' => class_basename($assignment->assignable_type),
        ];

        $results['assignments'][] = $assignmentInfo;

        if ($isVerbose) {
            $this->info('');
            $this->info("Processing {$type} reminder for: {$assignmentInfo['quiz_title']}");
            $this->info("  Deadline: {$assignmentInfo['deadline']}");
            $this->info("  Assigned to: {$assignmentInfo['assignable_type']}");
        }

        // Get students who haven't completed the quiz
        $students = $assignment->getStudentsWithoutCompletedAttempts();

        if ($students->isEmpty()) {
            $results['skipped']++;
            if ($isVerbose) {
                $this->info('  Skipped - all students have completed the quiz');
            }

            return;
        }

        if ($isVerbose) {
            $this->info("  Students to notify: {$students->count()}");
        }

        if ($isDryRun) {
            $this->info("  [DRY RUN] Would send {$type} reminder to {$students->count()} students");
            if ($type === '24h') {
                $results['24h_reminders_sent']++;
            } else {
                $results['1h_reminders_sent']++;
            }
            $results['students_notified'] += $students->count();

            return;
        }

        // Send notifications
        $notifiedCount = $assignment->notifyDeadlineApproaching($type);

        if ($type === '24h') {
            $results['24h_reminders_sent']++;
        } else {
            $results['1h_reminders_sent']++;
        }
        $results['students_notified'] += $notifiedCount;

        if ($isVerbose) {
            $this->info("  Sent {$type} reminder to {$notifiedCount} students");
        }
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results, bool $isDryRun): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info('');
        $this->info("Quiz Deadline Reminders {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        $totalAssignments = count($results['assignments']);
        $totalReminders = $results['24h_reminders_sent'] + $results['1h_reminders_sent'];

        if ($totalAssignments === 0) {
            $this->info('No quiz assignments requiring deadline reminders at this time.');

            return;
        }

        $this->info("Assignments processed: {$totalAssignments}");
        $this->info("24-hour reminders sent: {$results['24h_reminders_sent']}");
        $this->info("1-hour reminders sent: {$results['1h_reminders_sent']}");
        $this->info("Total students notified: {$results['students_notified']}");
        $this->info("Skipped (all completed): {$results['skipped']}");

        // Show errors if any
        if (! empty($results['errors'])) {
            $this->error('');
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - Quiz \"{$error['quiz_title']}\": {$error['error']}");
            }
        }

        $this->info('');
        $this->info('Quiz deadline reminder processing completed.');
    }
}
