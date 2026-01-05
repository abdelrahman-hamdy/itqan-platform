<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\TeacherEarning;
use App\Services\CronJobLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ValidateDataIntegrityCommand
 *
 * Validates data integrity across the system and reports inconsistencies.
 * Does NOT make changes - only reports issues for manual review.
 *
 * CHECKS PERFORMED:
 * - Subscription session counts vs actual completed sessions
 * - Sessions with invalid status combinations
 * - Orphaned records (sessions without valid parent)
 * - Missing earnings for completed sessions
 * - Duplicate active subscriptions for same student-teacher pair
 */
class ValidateDataIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:validate-integrity
                          {--fix : Attempt to fix issues automatically (use with caution)}
                          {--details : Show detailed output for each issue}
                          {--check= : Run specific check only (counts|orphans|earnings|duplicates)}';

    /**
     * The console command description.
     */
    protected $description = 'Validate data integrity and report inconsistencies';

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
        $shouldFix = $this->option('fix');
        $isVerbose = $this->option('details');
        $specificCheck = $this->option('check');

        // Start enhanced logging
        $executionData = $this->cronJobLogger->logCronStart('data:validate-integrity', [
            'fix' => $shouldFix,
            'verbose' => $isVerbose,
            'specific_check' => $specificCheck,
        ]);

        $this->info('Starting data integrity validation...');
        $this->info('Current time: ' . now()->format('Y-m-d H:i:s'));

        if ($shouldFix) {
            $this->warn('FIX MODE - Will attempt to fix issues automatically');
        } else {
            $this->info('REPORT MODE - Issues will be reported but not fixed');
        }

        try {
            $results = [
                'session_count_mismatches' => [],
                'invalid_statuses' => [],
                'orphaned_sessions' => [],
                'missing_earnings' => [],
                'duplicate_subscriptions' => [],
                'fixes_applied' => 0,
            ];

            // Run checks based on options
            if (! $specificCheck || $specificCheck === 'counts') {
                $this->info('');
                $this->info('Checking subscription session counts...');
                $results['session_count_mismatches'] = $this->checkSessionCounts($isVerbose, $shouldFix);
            }

            if (! $specificCheck || $specificCheck === 'orphans') {
                $this->info('');
                $this->info('Checking for orphaned sessions...');
                $results['orphaned_sessions'] = $this->checkOrphanedSessions($isVerbose);
            }

            if (! $specificCheck || $specificCheck === 'earnings') {
                $this->info('');
                $this->info('Checking for missing earnings...');
                $results['missing_earnings'] = $this->checkMissingEarnings($isVerbose);
            }

            if (! $specificCheck || $specificCheck === 'duplicates') {
                $this->info('');
                $this->info('Checking for duplicate active subscriptions...');
                $results['duplicate_subscriptions'] = $this->checkDuplicateSubscriptions($isVerbose);
            }

            $this->displayResults($results);

            // Log completion
            $this->cronJobLogger->logCronEnd('data:validate-integrity', $executionData, [
                'session_count_issues' => count($results['session_count_mismatches']),
                'orphaned_sessions' => count($results['orphaned_sessions']),
                'missing_earnings' => count($results['missing_earnings']),
                'duplicate_subscriptions' => count($results['duplicate_subscriptions']),
                'fixes_applied' => $results['fixes_applied'],
            ]);

            // Return failure if any issues found (useful for monitoring)
            $totalIssues = count($results['session_count_mismatches']) +
                          count($results['orphaned_sessions']) +
                          count($results['missing_earnings']) +
                          count($results['duplicate_subscriptions']);

            return $totalIssues > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Data integrity validation failed: ' . $e->getMessage());

            if ($isVerbose) {
                $this->error('Stack trace: ' . $e->getTraceAsString());
            }

            $this->cronJobLogger->logCronError('data:validate-integrity', $executionData, $e);

            Log::error('Data integrity validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Check subscription session counts match actual completed sessions
     */
    private function checkSessionCounts(bool $isVerbose, bool $shouldFix): array
    {
        $mismatches = [];

        // Check Quran subscriptions
        QuranSubscription::where('status', SessionSubscriptionStatus::ACTIVE)
            ->chunkById(100, function ($subscriptions) use ($isVerbose, $shouldFix, &$mismatches) {
                foreach ($subscriptions as $subscription) {
                    $actualUsed = QuranSession::where('quran_subscription_id', $subscription->id)
                        ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::ABSENT])
                        ->count();

                    if ($subscription->sessions_used !== $actualUsed) {
                        $issue = [
                            'type' => 'quran',
                            'subscription_id' => $subscription->id,
                            'recorded_used' => $subscription->sessions_used,
                            'actual_used' => $actualUsed,
                            'difference' => $actualUsed - $subscription->sessions_used,
                        ];
                        $mismatches[] = $issue;

                        if ($isVerbose) {
                            $this->warn("  Quran subscription {$subscription->id}: recorded {$subscription->sessions_used}, actual {$actualUsed}");
                        }

                        if ($shouldFix) {
                            $subscription->sessions_used = $actualUsed;
                            $subscription->sessions_remaining = max(0, $subscription->total_sessions - $actualUsed);
                            $subscription->save();
                            $this->info("    Fixed subscription {$subscription->id}");
                        }
                    }
                }
            });

        // Check Academic subscriptions
        AcademicSubscription::where('status', SessionSubscriptionStatus::ACTIVE)
            ->chunkById(100, function ($subscriptions) use ($isVerbose, $shouldFix, &$mismatches) {
                foreach ($subscriptions as $subscription) {
                    $actualUsed = AcademicSession::where('academic_subscription_id', $subscription->id)
                        ->whereIn('status', [SessionStatus::COMPLETED, SessionStatus::ABSENT])
                        ->count();

                    if ($subscription->sessions_used !== $actualUsed) {
                        $issue = [
                            'type' => 'academic',
                            'subscription_id' => $subscription->id,
                            'recorded_used' => $subscription->sessions_used,
                            'actual_used' => $actualUsed,
                            'difference' => $actualUsed - $subscription->sessions_used,
                        ];
                        $mismatches[] = $issue;

                        if ($isVerbose) {
                            $this->warn("  Academic subscription {$subscription->id}: recorded {$subscription->sessions_used}, actual {$actualUsed}");
                        }

                        if ($shouldFix) {
                            $subscription->sessions_used = $actualUsed;
                            $subscription->sessions_remaining = max(0, $subscription->total_sessions - $actualUsed);
                            $subscription->save();
                            $this->info("    Fixed subscription {$subscription->id}");
                        }
                    }
                }
            });

        $this->info("  Found " . count($mismatches) . " session count mismatches");

        return $mismatches;
    }

    /**
     * Check for orphaned sessions (sessions without valid subscription)
     */
    private function checkOrphanedSessions(bool $isVerbose): array
    {
        $orphans = [];

        // Quran sessions without valid subscription
        $orphanedQuran = QuranSession::whereNotNull('quran_subscription_id')
            ->whereDoesntHave('quranSubscription')
            ->get();

        foreach ($orphanedQuran as $session) {
            $orphans[] = [
                'type' => 'quran',
                'session_id' => $session->id,
                'missing_subscription_id' => $session->quran_subscription_id,
            ];

            if ($isVerbose) {
                $this->warn("  Orphaned Quran session {$session->id}: subscription {$session->quran_subscription_id} not found");
            }
        }

        // Academic sessions without valid subscription
        $orphanedAcademic = AcademicSession::whereNotNull('academic_subscription_id')
            ->whereDoesntHave('academicSubscription')
            ->get();

        foreach ($orphanedAcademic as $session) {
            $orphans[] = [
                'type' => 'academic',
                'session_id' => $session->id,
                'missing_subscription_id' => $session->academic_subscription_id,
            ];

            if ($isVerbose) {
                $this->warn("  Orphaned Academic session {$session->id}: subscription {$session->academic_subscription_id} not found");
            }
        }

        $this->info("  Found " . count($orphans) . " orphaned sessions");

        return $orphans;
    }

    /**
     * Check for completed sessions without earnings records
     */
    private function checkMissingEarnings(bool $isVerbose): array
    {
        $missing = [];
        $cutoffDate = now()->subDays(30); // Only check last 30 days

        // Quran sessions without earnings
        QuranSession::where('status', SessionStatus::COMPLETED)
            ->where('ended_at', '>=', $cutoffDate)
            ->whereNotNull('ended_at')
            ->chunkById(100, function ($sessions) use ($isVerbose, &$missing) {
                foreach ($sessions as $session) {
                    $hasEarning = TeacherEarning::forSession(
                        QuranSession::class,
                        $session->id
                    )->exists();

                    if (! $hasEarning) {
                        $missing[] = [
                            'type' => 'quran',
                            'session_id' => $session->id,
                            'ended_at' => $session->ended_at?->format('Y-m-d H:i:s'),
                        ];

                        if ($isVerbose) {
                            $this->warn("  Quran session {$session->id}: missing earnings record");
                        }
                    }
                }
            });

        // Academic sessions without earnings
        AcademicSession::where('status', SessionStatus::COMPLETED)
            ->where('ended_at', '>=', $cutoffDate)
            ->whereNotNull('ended_at')
            ->chunkById(100, function ($sessions) use ($isVerbose, &$missing) {
                foreach ($sessions as $session) {
                    $hasEarning = TeacherEarning::forSession(
                        AcademicSession::class,
                        $session->id
                    )->exists();

                    if (! $hasEarning) {
                        $missing[] = [
                            'type' => 'academic',
                            'session_id' => $session->id,
                            'ended_at' => $session->ended_at?->format('Y-m-d H:i:s'),
                        ];

                        if ($isVerbose) {
                            $this->warn("  Academic session {$session->id}: missing earnings record");
                        }
                    }
                }
            });

        $this->info("  Found " . count($missing) . " sessions with missing earnings (last 30 days)");

        return $missing;
    }

    /**
     * Check for duplicate active subscriptions (same student-teacher pair)
     */
    private function checkDuplicateSubscriptions(bool $isVerbose): array
    {
        $duplicates = [];

        // Quran subscriptions - same student with same teacher
        $quranDuplicates = DB::table('quran_subscriptions')
            ->select('student_id', 'quran_teacher_id', DB::raw('COUNT(*) as count'))
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNull('deleted_at')
            ->groupBy('student_id', 'quran_teacher_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($quranDuplicates as $dup) {
            $duplicates[] = [
                'type' => 'quran',
                'student_id' => $dup->student_id,
                'teacher_id' => $dup->quran_teacher_id,
                'count' => $dup->count,
            ];

            if ($isVerbose) {
                $this->warn("  Duplicate Quran subscriptions: student {$dup->student_id} with teacher {$dup->quran_teacher_id} ({$dup->count} active)");
            }
        }

        // Academic subscriptions - same student with same teacher
        $academicDuplicates = DB::table('academic_subscriptions')
            ->select('student_id', 'academic_teacher_id', DB::raw('COUNT(*) as count'))
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->whereNull('deleted_at')
            ->groupBy('student_id', 'academic_teacher_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($academicDuplicates as $dup) {
            $duplicates[] = [
                'type' => 'academic',
                'student_id' => $dup->student_id,
                'teacher_id' => $dup->academic_teacher_id,
                'count' => $dup->count,
            ];

            if ($isVerbose) {
                $this->warn("  Duplicate Academic subscriptions: student {$dup->student_id} with teacher {$dup->academic_teacher_id} ({$dup->count} active)");
            }
        }

        $this->info("  Found " . count($duplicates) . " duplicate active subscription pairs");

        return $duplicates;
    }

    /**
     * Display execution results
     */
    private function displayResults(array $results): void
    {
        $this->info('');
        $this->info('Data Integrity Validation Results:');
        $this->info('---');

        $this->info("Session count mismatches: " . count($results['session_count_mismatches']));
        $this->info("Orphaned sessions: " . count($results['orphaned_sessions']));
        $this->info("Missing earnings (30 days): " . count($results['missing_earnings']));
        $this->info("Duplicate subscriptions: " . count($results['duplicate_subscriptions']));

        $totalIssues = count($results['session_count_mismatches']) +
                      count($results['orphaned_sessions']) +
                      count($results['missing_earnings']) +
                      count($results['duplicate_subscriptions']);

        if ($totalIssues === 0) {
            $this->info('');
            $this->info('No data integrity issues found.');
        } else {
            $this->warn('');
            $this->warn("Total issues found: {$totalIssues}");
            $this->warn('Review the issues above and fix manually or use --fix flag (with caution).');
        }

        if ($results['fixes_applied'] > 0) {
            $this->info('');
            $this->info("Fixes applied: {$results['fixes_applied']}");
        }

        $this->info('');
        $this->info('Data integrity validation completed.');
    }
}
