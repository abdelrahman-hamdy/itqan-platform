<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use App\Services\EarningsCalculationService;
use App\Services\SessionSettingsService;
use App\Services\Traits\AttendanceCalculatorTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Repair attendance data pipeline: recalculate, backfill flags, fix earnings, flag manual review.
 */
class RepairAttendanceDataCommand extends Command
{
    use AttendanceCalculatorTrait;

    protected $signature = 'attendance:repair
                          {--phase=all : Phase to run: A, B, C, D, or all}
                          {--since=2026-04-01 : Only process sessions ended after this date}
                          {--academy-id= : Limit to a specific academy}
                          {--dry-run : Preview changes without saving}
                          {--session-ids= : Comma-separated session IDs to process}
                          {--force-recalculate-legacy : Reset is_calculated on left/late rows so they get recalculated with new percentage thresholds}';

    protected $description = 'Repair attendance data: recalculate, backfill teacher flags, fix earnings, flag for manual review';

    private array $sessionClasses = [
        'quran' => QuranSession::class,
        'academic' => AcademicSession::class,
        'interactive' => InteractiveCourseSession::class,
    ];

    public function handle(): int
    {
        $phase = strtoupper($this->option('phase'));
        $since = Carbon::parse($this->option('since'));
        $academyId = $this->option('academy-id');
        $isDryRun = $this->option('dry-run');
        $sessionIdFilter = $this->option('session-ids')
            ? array_map('intval', explode(',', $this->option('session-ids')))
            : null;

        if ($isDryRun) {
            $this->warn('DRY RUN MODE — no changes will be made');
        }

        $this->info("Attendance Repair — phases: {$phase}, since: {$since->toDateString()}");
        $this->newLine();

        $phases = $phase === 'ALL' ? ['A', 'B', 'C', 'D'] : [$phase];

        foreach ($phases as $p) {
            match ($p) {
                'A' => $this->phaseA($since, $academyId, $isDryRun, $sessionIdFilter),
                'B' => $this->phaseB($since, $academyId, $isDryRun, $sessionIdFilter),
                'C' => $this->phaseC($since, $academyId, $isDryRun, $sessionIdFilter),
                'D' => $this->phaseD($since, $academyId, $isDryRun),
                default => $this->error("Unknown phase: {$p}"),
            };
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Phase A: Recalculate uncalculated MeetingAttendance records.
     *
     * With --force-recalculate-legacy: first resets is_calculated on rows
     * with deprecated left/late statuses so they get recalculated through
     * the new percentage-based thresholds.
     */
    private function phaseA(Carbon $since, ?int $academyId, bool $isDryRun, ?array $sessionIdFilter): void
    {
        $this->info('=== Phase A: Recalculate uncalculated MeetingAttendance records ===');

        // Reset legacy left/late rows so they go through the new percentage logic
        if ($this->option('force-recalculate-legacy')) {
            $legacyQuery = MeetingAttendance::whereIn('attendance_status', ['left', 'late'])
                ->where('is_calculated', true);

            $legacyCount = $legacyQuery->count();
            $this->line("  Legacy left/late rows to reset: {$legacyCount}");

            if ($legacyCount > 0 && ! $isDryRun) {
                MeetingAttendance::whereIn('attendance_status', ['left', 'late'])
                    ->where('is_calculated', true)
                    ->update(['is_calculated' => false]);
                $this->line("  Reset {$legacyCount} rows to is_calculated=false");
            } elseif ($isDryRun) {
                $this->line("  [DRY] Would reset {$legacyCount} rows to is_calculated=false");
            }
        }

        $processed = 0;
        $errors = 0;

        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since)
                ->whereHas('meetingAttendances', fn ($q) => $q->where('is_calculated', false));

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
            if ($sessionIdFilter) {
                $query->whereIn('id', $sessionIdFilter);
            }

            $sessions = $query->get();
            $this->line("  {$type}: {$sessions->count()} sessions with uncalculated attendance");

            foreach ($sessions as $session) {
                if ($isDryRun) {
                    $uncalc = $session->meetingAttendances()->where('is_calculated', false)->count();
                    $this->line("    [DRY] Session {$session->id}: {$uncalc} uncalculated records");
                    $processed++;

                    continue;
                }

                try {
                    // Dispatch synchronously (dispatchSync bypasses the queue)
                    \App\Jobs\CalculateSessionForAttendance::dispatchSync(
                        $session->id,
                        $class
                    );
                    $processed++;
                    $this->line("    Session {$session->id}: recalculated");
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("    Session {$session->id}: ERROR — {$e->getMessage()}");
                    Log::error('RepairAttendanceData Phase A error', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("  Phase A complete: {$processed} processed, {$errors} errors");
    }

    /**
     * Phase B: Backfill teacher_attendance_status and counts_for_teacher.
     */
    private function phaseB(Carbon $since, ?int $academyId, bool $isDryRun, ?array $sessionIdFilter): void
    {
        $this->info('=== Phase B: Backfill teacher attendance flags ===');

        $settingsService = app(SessionSettingsService::class);
        $processed = 0;
        $errors = 0;

        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since)
                ->whereNull('teacher_attendance_status');

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
            if ($sessionIdFilter) {
                $query->whereIn('id', $sessionIdFilter);
            }

            $sessions = $query->get();
            $this->line("  {$type}: {$sessions->count()} sessions with NULL teacher_attendance_status");

            foreach ($sessions as $session) {
                try {
                    // Find teacher's MeetingAttendance (try with session_type filter first, then without)
                    $teacherAtt = MeetingAttendance::where('session_id', $session->id)
                        ->when($type === 'quran', fn ($q) => $q->whereIn('session_type', ['individual', 'group', 'trial']))
                        ->when($type !== 'quran', fn ($q) => $q->where('session_type', $type))
                        ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
                        ->first();

                    // Fallback: try without session_type filter (legacy data)
                    if (! $teacherAtt) {
                        $teacherAtt = MeetingAttendance::where('session_id', $session->id)
                            ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
                            ->first();
                    }

                    $sessionDuration = $session->duration_minutes ?? 60;
                    $fullPercent = $settingsService->getTeacherFullAttendancePercent($session);
                    $partialPercent = $settingsService->getTeacherPartialAttendancePercent($session);

                    if ($teacherAtt && $teacherAtt->first_join_time) {
                        $statusValue = $this->calculateTeacherAttendanceStatus(
                            $teacherAtt->first_join_time,
                            $sessionDuration,
                            $teacherAtt->total_duration_minutes ?? 0,
                            $fullPercent,
                            $partialPercent,
                        );
                        $teacherStatus = AttendanceStatus::from($statusValue);
                    } else {
                        $teacherStatus = AttendanceStatus::ABSENT;
                    }

                    $teacherCounts = $teacherStatus !== AttendanceStatus::ABSENT;

                    if ($isDryRun) {
                        $this->line("    [DRY] Session {$session->id}: teacher={$teacherStatus->value}, counts={$teacherCounts}, duration=".($teacherAtt?->total_duration_minutes ?? 0).'min');
                        $processed++;

                        continue;
                    }

                    $updateData = [
                        'teacher_attendance_status' => $teacherStatus->value,
                        'teacher_attendance_calculated_at' => now(),
                    ];

                    // Only set counts_for_teacher if not already overridden by admin
                    if ($session->counts_for_teacher_set_by === null) {
                        $updateData['counts_for_teacher'] = $teacherCounts;
                    }

                    $session->update($updateData);
                    $processed++;
                    $this->line("    Session {$session->id}: teacher={$teacherStatus->value}, counts={$teacherCounts}");
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("    Session {$session->id}: ERROR — {$e->getMessage()}");
                    Log::error('RepairAttendanceData Phase B error', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("  Phase B complete: {$processed} processed, {$errors} errors");
    }

    /**
     * Phase C: Calculate missing TeacherEarning records.
     */
    private function phaseC(Carbon $since, ?int $academyId, bool $isDryRun, ?array $sessionIdFilter): void
    {
        $this->info('=== Phase C: Calculate missing earnings ===');

        $earningsService = app(EarningsCalculationService::class);
        $processed = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since);

            // Exclude trial quran sessions
            if ($type === 'quran') {
                $query->where('session_type', '!=', 'trial');
            }

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
            if ($sessionIdFilter) {
                $query->whereIn('id', $sessionIdFilter);
            }

            // Only sessions where teacher was counted (or might be eligible)
            $query->where(function ($q) {
                $q->where('counts_for_teacher', true)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('counts_for_teacher')
                            ->where('actual_duration_minutes', '>', 0);
                    });
            });

            $sessions = $query->get();

            // Filter out sessions that already have earnings (no relationship, use query)
            $existingEarningSessionIds = TeacherEarning::withoutGlobalScopes()
                ->where('session_type', $class)
                ->whereIn('session_id', $sessions->pluck('id'))
                ->pluck('session_id')
                ->toArray();

            $sessions = $sessions->reject(fn ($s) => in_array($s->id, $existingEarningSessionIds));
            $this->line("  {$type}: {$sessions->count()} sessions missing earnings");

            foreach ($sessions as $session) {
                $processed++;

                if ($isDryRun) {
                    $this->line("    [DRY] Session {$session->id}: would calculate earnings");

                    continue;
                }

                try {
                    $earning = $earningsService->calculateSessionEarnings($session);

                    if ($earning) {
                        $created++;
                        $this->line("    Session {$session->id}: earning={$earning->amount} ({$earning->calculation_method})");
                    } else {
                        $skipped++;
                        $this->line("    Session {$session->id}: skipped (not eligible or 0 amount)");
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("    Session {$session->id}: ERROR — {$e->getMessage()}");
                    Log::error('RepairAttendanceData Phase C error', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("  Phase C complete: {$processed} processed, {$created} created, {$skipped} skipped, {$errors} errors");
    }

    /**
     * Phase D: Flag sessions with ZERO MeetingAttendance data for manual review.
     */
    private function phaseD(Carbon $since, ?int $academyId, bool $isDryRun): void
    {
        $this->info('=== Phase D: Flag sessions with NO attendance data for manual review ===');

        $csvRows = [];
        $csvRows[] = ['session_type', 'session_id', 'academy_id', 'teacher_id', 'teacher_name', 'scheduled_at', 'ended_at', 'actual_duration_minutes', 'counts_for_teacher', 'has_earning'];

        $totalCount = 0;

        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since)
                ->whereDoesntHave('meetingAttendances');

            // Exclude trial quran sessions
            if ($type === 'quran') {
                $query->where('session_type', '!=', 'trial');
            }

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }

            // Eager-load teacher relationship
            if ($type === 'quran') {
                $query->with('quranTeacher');
            } elseif ($type === 'academic') {
                $query->with('academicTeacher.user');
            } elseif ($type === 'interactive') {
                $query->with('course.assignedTeacher.user');
            }

            $sessions = $query->get();
            $this->line("  {$type}: {$sessions->count()} sessions with zero attendance data");
            $totalCount += $sessions->count();

            // Batch-load earnings existence to avoid N+1 queries
            $sessionIdsWithEarnings = $sessions->isNotEmpty()
                ? TeacherEarning::withoutGlobalScopes()
                    ->where('session_type', $class)
                    ->whereIn('session_id', $sessions->pluck('id'))
                    ->pluck('session_id')
                    ->flip()
                    ->toArray()
                : [];

            foreach ($sessions as $session) {
                $teacherName = $this->getTeacherName($session, $type);
                $teacherId = $this->getTeacherIdFromSession($session, $type);
                $hasEarning = isset($sessionIdsWithEarnings[$session->id]);

                $csvRows[] = [
                    $type,
                    $session->id,
                    $session->academy_id ?? '',
                    $teacherId,
                    $teacherName,
                    $session->scheduled_at?->toDateTimeString() ?? '',
                    $session->ended_at?->toDateTimeString() ?? '',
                    $session->actual_duration_minutes ?? 0,
                    $session->counts_for_teacher === null ? 'NULL' : ($session->counts_for_teacher ? 'YES' : 'NO'),
                    $hasEarning ? 'YES' : 'NO',
                ];

                $this->line("    Session {$session->id} ({$type}): teacher={$teacherName}, duration={$session->actual_duration_minutes}min, earning=".($hasEarning ? 'YES' : 'NO'));
            }
        }

        // Write CSV file
        $csvPath = storage_path('logs/attendance-manual-review-'.now()->format('Y-m-d-His').'.csv');

        if (! $isDryRun && $totalCount > 0) {
            $csvContent = '';
            foreach ($csvRows as $row) {
                $csvContent .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row))."\n";
            }
            File::put($csvPath, $csvContent);
            $this->info("  CSV written to: {$csvPath}");
        }

        $this->info("  Phase D complete: {$totalCount} sessions flagged for manual review");
        if ($totalCount > 0) {
            $this->warn('  These sessions have NO MeetingAttendance data. An admin must manually verify attendance and set counts_for_teacher.');
        }
    }

    private function getTeacherName($session, string $type): string
    {
        return match ($type) {
            'quran' => $session->quranTeacher?->name ?? 'Unknown',
            'academic' => $session->academicTeacher?->user?->name ?? 'Unknown',
            'interactive' => $session->course?->assignedTeacher?->user?->name ?? 'Unknown',
            default => 'Unknown',
        };
    }

    private function getTeacherIdFromSession($session, string $type): string
    {
        return match ($type) {
            'quran' => (string) ($session->quran_teacher_id ?? ''),
            'academic' => (string) ($session->academic_teacher_id ?? ''),
            'interactive' => (string) ($session->course?->assigned_teacher_id ?? ''),
            default => '',
        };
    }
}
