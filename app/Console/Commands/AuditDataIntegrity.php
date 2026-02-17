<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDataIntegrity extends Command
{
    protected $signature = 'app:audit-data
                            {--academy= : Filter by academy subdomain (default: all)}
                            {--fix : Attempt to fix safe issues (like null dates on active subscriptions)}';

    protected $description = 'Audit production data for orphaned records, status inconsistencies, and integrity issues';

    protected array $issues = [];

    protected int $checksRun = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->info('DATA INTEGRITY AUDIT');
        $this->info('====================');
        $this->newLine();

        $this->runCheck('Orphaned Users (deleted academy)', fn () => $this->checkOrphanedUsers());
        $this->runCheck('Orphaned Student Profiles (no user)', fn () => $this->checkOrphanedStudentProfiles());
        $this->runCheck('Orphaned Teacher Profiles (no user)', fn () => $this->checkOrphanedTeacherProfiles());
        $this->runCheck('Orphaned Subscriptions (missing student/teacher)', fn () => $this->checkOrphanedSubscriptions());
        $this->runCheck('Orphaned Sessions (missing student/teacher)', fn () => $this->checkOrphanedSessions());
        $this->runCheck('Orphaned Reports (missing session)', fn () => $this->checkOrphanedReports());
        $this->runCheck('Active Subscriptions with NULL dates', fn () => $this->checkActiveSubscriptionsWithoutDates());
        $this->runCheck('Pending Subscriptions older than 7 days', fn () => $this->checkStalePendingSubscriptions());
        $this->runCheck('Payment Status Mismatches', fn () => $this->checkPaymentStatusMismatches());
        $this->runCheck('Completed Sessions without reports', fn () => $this->checkCompletedSessionsWithoutReports());
        $this->runCheck('Session Date Anomalies', fn () => $this->checkSessionDateAnomalies());
        $this->runCheck('Duplicate Subscription Codes', fn () => $this->checkDuplicateSubscriptionCodes());
        $this->runCheck('Duplicate User Emails', fn () => $this->checkDuplicateEmails());
        $this->runCheck('Sessions Outside Subscription Dates', fn () => $this->checkSessionsOutsideSubscriptionDates());
        $this->runCheck('Subscription Counter Mismatches', fn () => $this->checkSubscriptionCounterMismatches());
        $this->runCheck('Soft-Deleted Users with Active Sessions', fn () => $this->checkDeletedUsersWithActiveSessions());
        $this->runCheck('NULL Tenant IDs', fn () => $this->checkNullTenantIds());

        $this->displayResults();
        $this->saveReport();

        return count($this->issues) > 0 ? 1 : 0;
    }

    protected function runCheck(string $name, callable $check): void
    {
        $this->checksRun++;
        $this->output->write("  [{$this->checksRun}] {$name}... ");

        try {
            $count = $check();
            if ($count > 0) {
                $this->output->writeln("<error>{$count} issues</error>");
            } else {
                $this->output->writeln('<info>OK</info>');
            }
        } catch (Throwable $e) {
            $this->output->writeln("<error>ERROR: {$e->getMessage()}</error>");
            $this->issues[] = [
                'check' => $name,
                'severity' => 'ERROR',
                'count' => 0,
                'details' => "Check failed: {$e->getMessage()}",
                'records' => [],
            ];
        }
    }

    // ========================================
    // Orphaned Record Checks
    // ========================================

    protected function checkOrphanedUsers(): int
    {
        $orphaned = DB::table('users')
            ->whereNotNull('academy_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('academies')
                    ->whereColumn('academies.id', 'users.academy_id')
                    ->whereNull('academies.deleted_at');
            })
            ->whereNull('users.deleted_at')
            ->select('users.id', 'users.email', 'users.academy_id', 'users.user_type')
            ->get();

        if ($orphaned->isNotEmpty()) {
            $this->addIssue('Orphaned Users (deleted academy)', 'HIGH', $orphaned->count(),
                'Users linked to non-existent or soft-deleted academies',
                $orphaned->map(fn ($u) => "User #{$u->id} ({$u->email}) → academy_id={$u->academy_id}")->toArray()
            );
        }

        return $orphaned->count();
    }

    protected function checkOrphanedStudentProfiles(): int
    {
        $orphaned = DB::table('student_profiles')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'student_profiles.user_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('student_profiles.deleted_at')
            ->select('student_profiles.id', 'student_profiles.user_id')
            ->get();

        if ($orphaned->isNotEmpty()) {
            $this->addIssue('Orphaned Student Profiles', 'HIGH', $orphaned->count(),
                'Student profiles linked to non-existent or deleted users',
                $orphaned->map(fn ($s) => "StudentProfile #{$s->id} → user_id={$s->user_id}")->toArray()
            );
        }

        return $orphaned->count();
    }

    protected function checkOrphanedTeacherProfiles(): int
    {
        $total = 0;

        // Quran teacher profiles
        $orphanedQuran = DB::table('quran_teacher_profiles')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'quran_teacher_profiles.user_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('quran_teacher_profiles.deleted_at')
            ->select('quran_teacher_profiles.id', 'quran_teacher_profiles.user_id')
            ->get();

        if ($orphanedQuran->isNotEmpty()) {
            $this->addIssue('Orphaned Quran Teacher Profiles', 'HIGH', $orphanedQuran->count(),
                'Quran teacher profiles linked to non-existent users',
                $orphanedQuran->map(fn ($t) => "QuranTeacher #{$t->id} → user_id={$t->user_id}")->toArray()
            );
            $total += $orphanedQuran->count();
        }

        // Academic teacher profiles
        $orphanedAcademic = DB::table('academic_teacher_profiles')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'academic_teacher_profiles.user_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('academic_teacher_profiles.deleted_at')
            ->select('academic_teacher_profiles.id', 'academic_teacher_profiles.user_id')
            ->get();

        if ($orphanedAcademic->isNotEmpty()) {
            $this->addIssue('Orphaned Academic Teacher Profiles', 'HIGH', $orphanedAcademic->count(),
                'Academic teacher profiles linked to non-existent users',
                $orphanedAcademic->map(fn ($t) => "AcademicTeacher #{$t->id} → user_id={$t->user_id}")->toArray()
            );
            $total += $orphanedAcademic->count();
        }

        return $total;
    }

    protected function checkOrphanedSubscriptions(): int
    {
        $total = 0;

        // Quran subscriptions with missing student
        $orphaned = DB::table('quran_subscriptions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'quran_subscriptions.student_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('quran_subscriptions.deleted_at')
            ->select('quran_subscriptions.id', 'quran_subscriptions.student_id', 'quran_subscriptions.subscription_code')
            ->get();

        if ($orphaned->isNotEmpty()) {
            $this->addIssue('Orphaned Quran Subscriptions (no student)', 'HIGH', $orphaned->count(),
                'Quran subscriptions referencing deleted students',
                $orphaned->map(fn ($s) => "Sub #{$s->id} ({$s->subscription_code}) → student_id={$s->student_id}")->toArray()
            );
            $total += $orphaned->count();
        }

        // Academic subscriptions with missing student
        $orphaned = DB::table('academic_subscriptions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'academic_subscriptions.student_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('academic_subscriptions.deleted_at')
            ->select('academic_subscriptions.id', 'academic_subscriptions.student_id', 'academic_subscriptions.subscription_code')
            ->get();

        if ($orphaned->isNotEmpty()) {
            $this->addIssue('Orphaned Academic Subscriptions (no student)', 'HIGH', $orphaned->count(),
                'Academic subscriptions referencing deleted students',
                $orphaned->map(fn ($s) => "Sub #{$s->id} ({$s->subscription_code}) → student_id={$s->student_id}")->toArray()
            );
            $total += $orphaned->count();
        }

        return $total;
    }

    protected function checkOrphanedSessions(): int
    {
        $total = 0;

        // Quran sessions with missing student
        $orphaned = DB::table('quran_sessions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'quran_sessions.student_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('quran_sessions.deleted_at')
            ->whereNotIn('quran_sessions.status', ['cancelled'])
            ->count();

        if ($orphaned > 0) {
            $this->addIssue('Orphaned Quran Sessions (no student)', 'MEDIUM', $orphaned,
                'Active quran sessions referencing deleted students');
            $total += $orphaned;
        }

        // Academic sessions with missing student
        $orphaned = DB::table('academic_sessions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'academic_sessions.student_id')
                    ->whereNull('users.deleted_at');
            })
            ->whereNull('academic_sessions.deleted_at')
            ->whereNotIn('academic_sessions.status', ['cancelled'])
            ->count();

        if ($orphaned > 0) {
            $this->addIssue('Orphaned Academic Sessions (no student)', 'MEDIUM', $orphaned,
                'Active academic sessions referencing deleted students');
            $total += $orphaned;
        }

        return $total;
    }

    protected function checkOrphanedReports(): int
    {
        // Student session reports with missing quran sessions
        // Note: student_session_reports does NOT have soft deletes (no deleted_at column)
        $orphaned = DB::table('student_session_reports')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('quran_sessions')
                    ->whereColumn('quran_sessions.id', 'student_session_reports.session_id')
                    ->whereNull('quran_sessions.deleted_at');
            })
            ->count();

        if ($orphaned > 0) {
            $this->addIssue('Orphaned Student Session Reports', 'MEDIUM', $orphaned,
                'Reports referencing deleted quran sessions');
        }

        return $orphaned;
    }

    // ========================================
    // Status Consistency Checks
    // ========================================

    protected function checkActiveSubscriptionsWithoutDates(): int
    {
        $total = 0;

        // Quran
        $quran = DB::table('quran_subscriptions')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhereNull('ends_at');
            })
            ->whereNull('deleted_at')
            ->select('id', 'subscription_code', 'starts_at', 'ends_at')
            ->get();

        if ($quran->isNotEmpty()) {
            $this->addIssue('Active Quran Subscriptions with NULL dates', 'HIGH', $quran->count(),
                'Active subscriptions missing starts_at or ends_at — may show as expired in UI',
                $quran->map(fn ($s) => "Sub #{$s->id} ({$s->subscription_code}) starts={$s->starts_at} ends={$s->ends_at}")->toArray()
            );
            $total += $quran->count();
        }

        // Academic
        $academic = DB::table('academic_subscriptions')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhereNull('ends_at');
            })
            ->whereNull('deleted_at')
            ->select('id', 'subscription_code', 'starts_at', 'ends_at')
            ->get();

        if ($academic->isNotEmpty()) {
            $this->addIssue('Active Academic Subscriptions with NULL dates', 'HIGH', $academic->count(),
                'Active subscriptions missing starts_at or ends_at',
                $academic->map(fn ($s) => "Sub #{$s->id} ({$s->subscription_code}) starts={$s->starts_at} ends={$s->ends_at}")->toArray()
            );
            $total += $academic->count();
        }

        return $total;
    }

    protected function checkStalePendingSubscriptions(): int
    {
        $staleDate = now()->subDays(7)->toDateTimeString();

        $stale = DB::table('academic_subscriptions')
            ->where('status', 'pending')
            ->where('created_at', '<', $staleDate)
            ->whereNull('deleted_at')
            ->count();

        $stale += DB::table('quran_subscriptions')
            ->where('status', 'pending')
            ->where('created_at', '<', $staleDate)
            ->whereNull('deleted_at')
            ->count();

        if ($stale > 0) {
            $this->addIssue('Stale Pending Subscriptions (>7 days)', 'LOW', $stale,
                'Subscriptions stuck in PENDING for over a week — likely abandoned payments');
        }

        return $stale;
    }

    protected function checkPaymentStatusMismatches(): int
    {
        $total = 0;

        // PAID but still PENDING status
        $paidPending = DB::table('academic_subscriptions')
            ->where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->select('id', 'subscription_code', 'status', 'payment_status')
            ->get();

        $paidPending2 = DB::table('quran_subscriptions')
            ->where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->select('id', 'subscription_code', 'status', 'payment_status')
            ->get();

        $combined = $paidPending->merge($paidPending2);
        if ($combined->isNotEmpty()) {
            $this->addIssue('Payment/Status Mismatch (paid but pending)', 'CRITICAL', $combined->count(),
                'Subscriptions marked as PAID but status still PENDING — payment processed but activation failed',
                $combined->map(fn ($s) => "Sub #{$s->id} ({$s->subscription_code}) status={$s->status} payment={$s->payment_status}")->toArray()
            );
            $total += $combined->count();
        }

        return $total;
    }

    protected function checkCompletedSessionsWithoutReports(): int
    {
        // Only check quran sessions since they're expected to have reports
        // Note: student_session_reports does NOT have soft deletes
        $noReports = DB::table('quran_sessions')
            ->where('status', 'completed')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('student_session_reports')
                    ->whereColumn('student_session_reports.session_id', 'quran_sessions.id');
            })
            ->whereNull('quran_sessions.deleted_at')
            ->where('quran_sessions.created_at', '>', now()->subMonths(3)->toDateTimeString())
            ->count();

        if ($noReports > 0) {
            $this->addIssue('Completed Quran Sessions without Reports', 'LOW', $noReports,
                'Quran sessions marked completed in last 3 months but no student reports created');
        }

        return $noReports;
    }

    protected function checkSessionDateAnomalies(): int
    {
        $total = 0;

        // Sessions where started_at > ended_at
        foreach (['quran_sessions', 'academic_sessions'] as $table) {
            $anomalies = DB::table($table)
                ->whereNotNull('started_at')
                ->whereNotNull('ended_at')
                ->whereColumn('started_at', '>', 'ended_at')
                ->whereNull('deleted_at')
                ->count();

            if ($anomalies > 0) {
                $this->addIssue("Date Anomaly: {$table} started > ended", 'MEDIUM', $anomalies,
                    'Sessions where started_at is after ended_at');
                $total += $anomalies;
            }
        }

        // Scheduled sessions in the past (>24hrs) still marked as SCHEDULED
        foreach (['quran_sessions', 'academic_sessions'] as $table) {
            $stale = DB::table($table)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<', now()->subDay()->toDateTimeString())
                ->whereNull('deleted_at')
                ->count();

            if ($stale > 0) {
                $this->addIssue("Stale Scheduled Sessions: {$table}", 'MEDIUM', $stale,
                    'Sessions scheduled >24hrs ago still in SCHEDULED status — scheduler may not be running');
                $total += $stale;
            }
        }

        return $total;
    }

    // ========================================
    // Duplicate Detection
    // ========================================

    protected function checkDuplicateSubscriptionCodes(): int
    {
        $total = 0;

        foreach (['quran_subscriptions', 'academic_subscriptions'] as $table) {
            $duplicates = DB::table($table)
                ->select('subscription_code', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('subscription_code')
                ->whereNull('deleted_at')
                ->groupBy('subscription_code')
                ->having('cnt', '>', 1)
                ->get();

            if ($duplicates->isNotEmpty()) {
                $this->addIssue("Duplicate Subscription Codes: {$table}", 'HIGH', $duplicates->count(),
                    'Multiple active subscriptions with the same code',
                    $duplicates->map(fn ($d) => "Code '{$d->subscription_code}' appears {$d->cnt} times")->toArray()
                );
                $total += $duplicates->count();
            }
        }

        return $total;
    }

    protected function checkDuplicateEmails(): int
    {
        $duplicates = DB::table('users')
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('email')
            ->whereNull('deleted_at')
            ->groupBy('email')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->addIssue('Duplicate User Emails', 'CRITICAL', $duplicates->count(),
                'Multiple active users with the same email address',
                $duplicates->map(fn ($d) => "'{$d->email}' appears {$d->cnt} times")->toArray()
            );
        }

        return $duplicates->count();
    }

    // ========================================
    // Relationship Integrity Checks
    // ========================================

    protected function checkSessionsOutsideSubscriptionDates(): int
    {
        // Academic sessions scheduled outside their subscription date range
        $outside = DB::table('academic_sessions')
            ->join('academic_subscriptions', 'academic_sessions.academic_subscription_id', '=', 'academic_subscriptions.id')
            ->whereNotNull('academic_subscriptions.starts_at')
            ->whereNotNull('academic_subscriptions.ends_at')
            ->where(function ($q) {
                $q->whereColumn('academic_sessions.scheduled_at', '<', 'academic_subscriptions.starts_at')
                    ->orWhereColumn('academic_sessions.scheduled_at', '>', 'academic_subscriptions.ends_at');
            })
            ->whereNull('academic_sessions.deleted_at')
            ->whereNull('academic_subscriptions.deleted_at')
            ->whereNotIn('academic_sessions.status', ['cancelled'])
            ->count();

        if ($outside > 0) {
            $this->addIssue('Sessions Outside Subscription Date Range', 'LOW', $outside,
                'Academic sessions scheduled before starts_at or after ends_at of their subscription');
        }

        return $outside;
    }

    protected function checkSubscriptionCounterMismatches(): int
    {
        // Academic subscriptions where actual session count doesn't match counter
        $mismatches = DB::select("
            SELECT
                s.id,
                s.subscription_code,
                s.total_sessions_scheduled,
                COUNT(sess.id) as actual_sessions
            FROM academic_subscriptions s
            LEFT JOIN academic_sessions sess
                ON sess.academic_subscription_id = s.id
                AND sess.deleted_at IS NULL
                AND sess.status != 'cancelled'
            WHERE s.deleted_at IS NULL
            AND s.status = 'active'
            GROUP BY s.id, s.subscription_code, s.total_sessions_scheduled
            HAVING actual_sessions != s.total_sessions_scheduled
                AND s.total_sessions_scheduled > 0
            LIMIT 20
        ");

        if (count($mismatches) > 0) {
            $this->addIssue('Subscription Counter Mismatches', 'MEDIUM', count($mismatches),
                'total_sessions_scheduled counter differs from actual session count',
                array_map(fn ($m) => "Sub #{$m->id} ({$m->subscription_code}): counter={$m->total_sessions_scheduled}, actual={$m->actual_sessions}", $mismatches)
            );
        }

        return count($mismatches);
    }

    protected function checkDeletedUsersWithActiveSessions(): int
    {
        $total = 0;

        foreach (['quran_sessions' => 'student_id', 'academic_sessions' => 'student_id'] as $table => $col) {
            $count = DB::table($table)
                ->join('users', "users.id", '=', "{$table}.{$col}")
                ->whereNotNull('users.deleted_at')
                ->whereNull("{$table}.deleted_at")
                ->whereIn("{$table}.status", ['scheduled', 'ready', 'ongoing'])
                ->count();

            if ($count > 0) {
                $this->addIssue("Deleted Users with Active Sessions ({$table})", 'HIGH', $count,
                    'Soft-deleted users still have sessions in scheduled/ready/ongoing status');
                $total += $count;
            }
        }

        return $total;
    }

    protected function checkNullTenantIds(): int
    {
        $tables = [
            'quran_sessions' => 'academy_id',
            'academic_sessions' => 'academy_id',
            'quran_subscriptions' => 'academy_id',
            'academic_subscriptions' => 'academy_id',
        ];

        $total = 0;
        foreach ($tables as $table => $col) {
            $count = DB::table($table)
                ->whereNull($col)
                ->whereNull('deleted_at')
                ->count();

            if ($count > 0) {
                $this->addIssue("NULL {$col} in {$table}", 'CRITICAL', $count,
                    "Records without tenant isolation — data leakage risk");
                $total += $count;
            }
        }

        return $total;
    }

    // ========================================
    // Output
    // ========================================

    protected function addIssue(string $check, string $severity, int $count, string $details, array $records = []): void
    {
        $this->issues[] = compact('check', 'severity', 'count', 'details', 'records');
    }

    protected function displayResults(): void
    {
        $this->newLine(2);
        $this->info('AUDIT RESULTS');
        $this->info('==============');
        $this->newLine();

        $this->line("  Checks run: {$this->checksRun}");
        $this->line('  Issues found: '.count($this->issues));
        $this->newLine();

        if (empty($this->issues)) {
            $this->info('All checks passed! No integrity issues found.');

            return;
        }

        // Group by severity
        $bySeverity = collect($this->issues)->groupBy('severity');

        foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'ERROR'] as $sev) {
            $issues = $bySeverity->get($sev, collect());
            if ($issues->isEmpty()) {
                continue;
            }

            $color = match ($sev) {
                'CRITICAL' => 'error',
                'HIGH' => 'error',
                'MEDIUM' => 'warn',
                'LOW' => 'comment',
                default => 'line',
            };

            $this->newLine();
            $this->{$color}("  [{$sev}] — {$issues->count()} issue(s)");

            foreach ($issues as $issue) {
                $this->line("    {$issue['check']} ({$issue['count']} records)");
                $this->line("      {$issue['details']}");

                if (! empty($issue['records'])) {
                    foreach (array_slice($issue['records'], 0, 5) as $record) {
                        $this->line("        - {$record}");
                    }
                    if (count($issue['records']) > 5) {
                        $remaining = count($issue['records']) - 5;
                        $this->line("        ... and {$remaining} more");
                    }
                }
            }
        }
    }

    protected function saveReport(): void
    {
        $filename = 'qa/reports/data-audit-'.date('Y-m-d-His').'.md';
        $dir = dirname(base_path($filename));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $report = "# Data Integrity Audit Report\n\n";
        $report .= '**Generated:** '.now()->format('Y-m-d H:i:s')."\n";
        $report .= "**Checks Run:** {$this->checksRun}\n";
        $report .= '**Issues Found:** '.count($this->issues)."\n\n";

        if (empty($this->issues)) {
            $report .= "All checks passed.\n";
        } else {
            $bySeverity = collect($this->issues)->groupBy('severity');

            foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'] as $sev) {
                $issues = $bySeverity->get($sev, collect());
                if ($issues->isEmpty()) {
                    continue;
                }

                $report .= "## {$sev}\n\n";
                foreach ($issues as $issue) {
                    $report .= "### {$issue['check']} ({$issue['count']} records)\n\n";
                    $report .= "{$issue['details']}\n\n";

                    if (! empty($issue['records'])) {
                        foreach (array_slice($issue['records'], 0, 10) as $record) {
                            $report .= "- {$record}\n";
                        }
                        if (count($issue['records']) > 10) {
                            $report .= '- ... and '.(count($issue['records']) - 10)." more\n";
                        }
                        $report .= "\n";
                    }
                }
            }
        }

        file_put_contents(base_path($filename), $report);
        $this->newLine();
        $this->info("Report saved: {$filename}");
    }
}
