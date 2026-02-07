<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\Services\CronJobLogger;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SendTrialSessionRemindersCommand
 *
 * Sends reminder notifications for upcoming trial sessions.
 * Runs hourly via scheduler.
 *
 * REMINDER SCHEDULE:
 * - 1 hour before trial session: Reminder to both student and teacher
 *
 * TIMEZONE HANDLING:
 * All times are stored in UTC. This command converts them to academy
 * timezone for display in notifications.
 */
class SendTrialSessionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'trials:send-reminders
                          {--dry-run : Show what would be done without actually sending}
                          {--details : Show detailed output for each reminder}';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder notifications for upcoming trial sessions (1 hour before)';

    public function __construct(
        private NotificationService $notificationService,
        private CronJobLogger $cronJobLogger
    ) {
        parent::__construct();
    }

    /**
     * Format datetime in academy timezone for notifications.
     */
    private function formatInAcademyTimezone(?Carbon $datetime, string $format = 'h:i A'): string
    {
        if (! $datetime) {
            return '';
        }

        $timezone = AcademyContextService::getTimezone();

        return $datetime->copy()->setTimezone($timezone)->format($format);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isVerbose = $this->option('details') || $isDryRun;

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('trials:send-reminders', [
            'dry_run' => $isDryRun,
            'verbose' => $isVerbose,
        ]);

        $this->info('Starting trial session reminder processing...');
        $this->info('Current time: '.now()->format('Y-m-d H:i:s'));

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual reminders will be sent');
        }

        try {
            $results = $this->processReminders($isDryRun, $isVerbose);

            $this->displayResults($results, $isDryRun);

            // Log completion
            $this->cronJobLogger->logCronEnd('trials:send-reminders', $executionData, $results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Trial reminder processing failed: '.$e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: '.$e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('trials:send-reminders', $executionData, $e);

            Log::error('Trial session reminder processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process and send trial session reminders
     */
    private function processReminders(bool $isDryRun, bool $isVerbose): array
    {
        $results = [
            'student_reminders_sent' => 0,
            'teacher_reminders_sent' => 0,
            'skipped' => 0,
            'errors' => [],
            'sessions' => [],
        ];

        // Find trial sessions starting in the next hour (between 55 and 65 minutes from now)
        $upcomingSessions = QuranSession::trial()
            ->where('status', SessionStatus::SCHEDULED)
            ->whereBetween('scheduled_at', [
                now()->addMinutes(55),
                now()->addMinutes(65),
            ])
            ->with(['quranTeacher', 'student', 'trialRequest', 'academy'])
            ->get();

        $this->info("Found {$upcomingSessions->count()} trial sessions starting in about 1 hour");

        foreach ($upcomingSessions as $session) {
            try {
                $sessionInfo = [
                    'id' => $session->id,
                    'session_code' => $session->session_code,
                    'scheduled_at' => $this->formatInAcademyTimezone($session->scheduled_at, 'Y-m-d h:i A'),
                    'student_name' => $session->student?->name ?? $session->trialRequest?->student_name ?? 'Unknown',
                    'teacher_name' => $session->quranTeacher?->name ?? 'Unknown',
                    'academy' => $session->academy?->name ?? 'Unknown',
                ];

                $results['sessions'][] = $sessionInfo;

                if ($isVerbose) {
                    $this->info('');
                    $this->info("Processing trial session: {$session->session_code}");
                    $this->info("  Student: {$sessionInfo['student_name']}");
                    $this->info("  Teacher: {$sessionInfo['teacher_name']}");
                    $this->info("  Scheduled: {$sessionInfo['scheduled_at']}");
                }

                if ($isDryRun) {
                    $this->info('  [DRY RUN] Would send reminder to student and teacher');
                    $results['student_reminders_sent']++;
                    $results['teacher_reminders_sent']++;

                    continue;
                }

                // Send reminder to student
                if ($session->student) {
                    $this->sendStudentReminder($session);
                    $results['student_reminders_sent']++;

                    if ($isVerbose) {
                        $this->info('  Sent reminder to student');
                    }
                } else {
                    $results['skipped']++;
                    if ($isVerbose) {
                        $this->warn('  Skipped student reminder - no student found');
                    }
                }

                // Send reminder to teacher
                if ($session->quranTeacher) {
                    $this->sendTeacherReminder($session);
                    $results['teacher_reminders_sent']++;

                    if ($isVerbose) {
                        $this->info('  Sent reminder to teacher');
                    }
                } else {
                    $results['skipped']++;
                    if ($isVerbose) {
                        $this->warn('  Skipped teacher reminder - no teacher found');
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'session_code' => $session->session_code,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to send trial session reminder', [
                    'session_id' => $session->id,
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
     * Send reminder notification to student
     */
    private function sendStudentReminder(QuranSession $session): void
    {
        // Build session URL
        $sessionUrl = "/student/sessions/{$session->id}";

        // Use role-specific TRIAL_SESSION_REMINDER_STUDENT type
        $this->notificationService->send(
            $session->student,
            NotificationType::TRIAL_SESSION_REMINDER_STUDENT,
            [
                'teacher_name' => $session->quranTeacher?->name ?? __('common.teacher'),
                'scheduled_time' => $this->formatInAcademyTimezone($session->scheduled_at),
            ],
            $sessionUrl,
            [
                'session_id' => $session->id,
                'session_type' => 'trial',
            ],
            true // Mark as important
        );

        // Also notify parent if exists with PARENT-specific type
        $parent = $session->student?->studentProfile?->parent?->user;
        if ($parent) {
            $this->notificationService->send(
                $parent,
                NotificationType::TRIAL_SESSION_REMINDER_PARENT,
                [
                    'student_name' => $session->student->name,
                    'teacher_name' => $session->quranTeacher?->name ?? __('common.teacher'),
                    'scheduled_time' => $this->formatInAcademyTimezone($session->scheduled_at),
                ],
                $sessionUrl,
                [
                    'session_id' => $session->id,
                    'session_type' => 'trial',
                ],
                true
            );
        }
    }

    /**
     * Send reminder notification to teacher
     */
    private function sendTeacherReminder(QuranSession $session): void
    {
        // Build session URL for teacher
        $sessionUrl = "/teacher-panel/quran-sessions/{$session->id}";

        // Use role-specific TRIAL_SESSION_REMINDER_TEACHER type
        $this->notificationService->send(
            $session->quranTeacher,
            NotificationType::TRIAL_SESSION_REMINDER_TEACHER,
            [
                'student_name' => $session->student?->name ?? $session->trialRequest?->student_name ?? __('common.student'),
                'scheduled_time' => $this->formatInAcademyTimezone($session->scheduled_at),
                'student_level' => $session->trialRequest?->level_label ?? __('common.unspecified'),
            ],
            $sessionUrl,
            [
                'session_id' => $session->id,
                'session_type' => 'trial',
            ],
            true // Mark as important
        );
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results, bool $isDryRun): void
    {
        $mode = $isDryRun ? 'Simulation' : 'Execution';
        $this->info('');
        $this->info("Trial Session Reminders {$mode} Results:");
        $this->info('═══════════════════════════════════════════════════');

        $totalSessions = count($results['sessions']);
        $totalReminders = $results['student_reminders_sent'] + $results['teacher_reminders_sent'];

        if ($totalSessions === 0) {
            $this->info('No trial sessions requiring reminders at this time.');

            return;
        }

        $this->info("Trial sessions processed: {$totalSessions}");
        $this->info("Student reminders sent: {$results['student_reminders_sent']}");
        $this->info("Teacher reminders sent: {$results['teacher_reminders_sent']}");
        $this->info("Total reminders sent: {$totalReminders}");
        $this->info("Skipped: {$results['skipped']}");

        // Show errors if any
        if (! empty($results['errors'])) {
            $this->error('');
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - Session {$error['session_code']}: {$error['error']}");
            }
        }

        $this->info('');
        $this->info('Trial session reminder processing completed.');
    }
}
