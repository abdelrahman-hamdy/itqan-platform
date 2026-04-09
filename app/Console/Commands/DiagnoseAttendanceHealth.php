<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Diagnose attendance system health — find missing, corrupted, or stuck data.
 */
class DiagnoseAttendanceHealth extends Command
{
    protected $signature = 'attendance:diagnose
                          {--since=2026-04-01 : Only check sessions ended after this date}
                          {--academy-id= : Limit to a specific academy}
                          {--detailed : Show all session IDs, not just counts}';

    protected $description = 'Diagnose attendance data health: find missing records, uncalculated attendance, missing earnings';

    private array $sessionClasses = [
        'quran' => QuranSession::class,
        'academic' => AcademicSession::class,
        'interactive' => InteractiveCourseSession::class,
    ];

    public function handle(): int
    {
        $since = Carbon::parse($this->option('since'));
        $academyId = $this->option('academy-id');
        $detailed = $this->option('detailed');

        $this->info("Attendance Health Diagnostic — sessions since {$since->toDateString()}");
        if ($academyId) {
            $this->info("Scoped to academy ID: {$academyId}");
        }
        $this->newLine();

        $results = [];

        // 1. Completed sessions with NO MeetingAttendance records
        $results[] = $this->checkNoAttendanceRecords($since, $academyId, $detailed);

        // 2. Uncalculated MeetingAttendance records
        $results[] = $this->checkUncalculatedAttendance($since, $detailed);

        // 3. NULL teacher_attendance_status
        $results[] = $this->checkNullTeacherAttendance($since, $academyId, $detailed);

        // 4. NULL counts_for_teacher
        $results[] = $this->checkNullCountsForTeacher($since, $academyId, $detailed);

        // 5. Missing TeacherEarning records
        $results[] = $this->checkMissingEarnings($since, $academyId, $detailed);

        // 6. Duplicate TeacherEarning records
        $results[] = $this->checkDuplicateEarnings();

        // 7. Zero-duration with cycle data
        $results[] = $this->checkZeroDurationWithCycles($since, $detailed);

        // 8. Stuck ONGOING sessions
        $results[] = $this->checkStuckOngoing($since, $academyId, $detailed);

        // Summary table
        $this->newLine();
        $this->info('=== SUMMARY ===');
        $this->table(
            ['#', 'Check', 'Count', 'Severity'],
            collect($results)->map(fn ($r, $i) => [
                $i + 1,
                $r['label'],
                $r['count'],
                $r['severity'],
            ])->toArray()
        );

        // 9. Focused window analysis (Apr 8-9)
        $this->newLine();
        $this->focusedWindowAnalysis($academyId);

        return self::SUCCESS;
    }

    /**
     * 1. Completed sessions with ZERO MeetingAttendance rows.
     */
    private function checkNoAttendanceRecords(Carbon $since, ?int $academyId, bool $detailed): array
    {
        $this->info('1. Completed sessions with NO MeetingAttendance records (data lost)');

        $ids = [];
        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since)
                ->whereDoesntHave('meetingAttendances');

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }

            $sessionIds = $query->pluck('id')->toArray();
            foreach ($sessionIds as $id) {
                $ids[] = "{$type}:{$id}";
            }
        }

        $count = count($ids);
        if ($detailed && $count > 0) {
            $this->warn('  IDs: '.implode(', ', array_slice($ids, 0, 50)).($count > 50 ? '... (+'.($count - 50).' more)' : ''));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'No MeetingAttendance records', 'count' => $count, 'severity' => $count > 0 ? 'CRITICAL' : 'OK'];
    }

    /**
     * 2. MeetingAttendance records with is_calculated = false.
     */
    private function checkUncalculatedAttendance(Carbon $since, bool $detailed): array
    {
        $this->info('2. Uncalculated MeetingAttendance records (is_calculated = false)');

        $query = MeetingAttendance::where('is_calculated', false)
            ->where('created_at', '>=', $since);

        $count = $query->count();

        if ($detailed && $count > 0) {
            $ids = $query->limit(50)->pluck('id')->toArray();
            $this->warn('  IDs: '.implode(', ', $ids).($count > 50 ? '... (+'.($count - 50).' more)' : ''));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'Uncalculated attendance', 'count' => $count, 'severity' => $count > 0 ? 'HIGH' : 'OK'];
    }

    /**
     * 3. Completed sessions with NULL teacher_attendance_status.
     */
    private function checkNullTeacherAttendance(Carbon $since, ?int $academyId, bool $detailed): array
    {
        $this->info('3. Completed sessions with NULL teacher_attendance_status');

        $ids = [];
        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since)
                ->whereNull('teacher_attendance_status');

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }

            $sessionIds = $query->pluck('id')->toArray();
            foreach ($sessionIds as $id) {
                $ids[] = "{$type}:{$id}";
            }
        }

        $count = count($ids);
        if ($detailed && $count > 0) {
            $this->warn('  IDs: '.implode(', ', array_slice($ids, 0, 50)).($count > 50 ? '... (+'.($count - 50).' more)' : ''));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'NULL teacher_attendance_status', 'count' => $count, 'severity' => $count > 0 ? 'HIGH' : 'OK'];
    }

    /**
     * 4. Completed sessions with NULL counts_for_teacher.
     */
    private function checkNullCountsForTeacher(Carbon $since, ?int $academyId, bool $detailed): array
    {
        $this->info('4. Completed sessions with NULL counts_for_teacher');

        $ids = [];
        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->where('ended_at', '>=', $since)
                ->whereNull('counts_for_teacher');

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }

            $sessionIds = $query->pluck('id')->toArray();
            foreach ($sessionIds as $id) {
                $ids[] = "{$type}:{$id}";
            }
        }

        $count = count($ids);
        if ($detailed && $count > 0) {
            $this->warn('  IDs: '.implode(', ', array_slice($ids, 0, 50)).($count > 50 ? '... (+'.($count - 50).' more)' : ''));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'NULL counts_for_teacher', 'count' => $count, 'severity' => $count > 0 ? 'HIGH' : 'OK'];
    }

    /**
     * 5. Completed non-trial sessions with NO TeacherEarning record.
     */
    private function checkMissingEarnings(Carbon $since, ?int $academyId, bool $detailed): array
    {
        $this->info('5. Completed sessions with NO TeacherEarning record');

        $ids = [];
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

            $sessionIds = $query->pluck('id')->toArray();

            if (! empty($sessionIds)) {
                $existingEarnings = TeacherEarning::withoutGlobalScopes()
                    ->where('session_type', $class)
                    ->whereIn('session_id', $sessionIds)
                    ->pluck('session_id')
                    ->toArray();

                $missing = array_diff($sessionIds, $existingEarnings);
                foreach ($missing as $id) {
                    $ids[] = "{$type}:{$id}";
                }
            }
        }

        $count = count($ids);
        if ($detailed && $count > 0) {
            $this->warn('  IDs: '.implode(', ', array_slice($ids, 0, 50)).($count > 50 ? '... (+'.($count - 50).' more)' : ''));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'Missing TeacherEarning', 'count' => $count, 'severity' => $count > 0 ? 'CRITICAL' : 'OK'];
    }

    /**
     * 6. Duplicate TeacherEarning records (same session_id + session_type).
     */
    private function checkDuplicateEarnings(): array
    {
        $this->info('6. Duplicate TeacherEarning records');

        $duplicates = DB::table('teacher_earnings')
            ->select('session_id', 'session_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('session_id', 'session_type')
            ->having('cnt', '>', 1)
            ->get();

        $count = $duplicates->count();
        if ($count > 0) {
            foreach ($duplicates->take(10) as $dup) {
                $this->warn("  session_id={$dup->session_id}, type={$dup->session_type}, count={$dup->cnt}");
            }
        }
        $this->line("  Found: {$count} duplicate groups");

        return ['label' => 'Duplicate earnings', 'count' => $count, 'severity' => $count > 0 ? 'HIGH' : 'OK'];
    }

    /**
     * 7. MeetingAttendance with total_duration_minutes=0 but join_leave_cycles has data.
     */
    private function checkZeroDurationWithCycles(Carbon $since, bool $detailed): array
    {
        $this->info('7. MeetingAttendance with 0 duration but has join/leave cycles');

        $query = MeetingAttendance::where('total_duration_minutes', 0)
            ->where('created_at', '>=', $since)
            ->whereNotNull('join_leave_cycles')
            ->where('join_leave_cycles', '!=', '[]')
            ->where('join_leave_cycles', '!=', 'null');

        $count = $query->count();

        if ($detailed && $count > 0) {
            $ids = $query->limit(50)->pluck('id')->toArray();
            $this->warn('  IDs: '.implode(', ', $ids));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'Zero duration with cycles', 'count' => $count, 'severity' => $count > 0 ? 'MEDIUM' : 'OK'];
    }

    /**
     * 8. Sessions stuck in ONGOING past expected end time.
     */
    private function checkStuckOngoing(Carbon $since, ?int $academyId, bool $detailed): array
    {
        $this->info('8. Sessions stuck in ONGOING status (past expected end time + 60min)');

        $ids = [];
        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::ONGOING)
                ->where('scheduled_at', '>=', $since)
                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL (COALESCE(duration_minutes, 60) + 60) MINUTE) < NOW()');

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }

            $sessionIds = $query->pluck('id')->toArray();
            foreach ($sessionIds as $id) {
                $ids[] = "{$type}:{$id}";
            }
        }

        $count = count($ids);
        if ($detailed && $count > 0) {
            $this->warn('  IDs: '.implode(', ', $ids));
        }
        $this->line("  Found: {$count}");

        return ['label' => 'Stuck ONGOING sessions', 'count' => $count, 'severity' => $count > 0 ? 'HIGH' : 'OK'];
    }

    /**
     * 9. Focused analysis on the restore window (Apr 8-9, 2026).
     */
    private function focusedWindowAnalysis(?int $academyId): void
    {
        $this->info('=== FOCUSED WINDOW: Apr 8-9, 2026 (restore window) ===');

        $windowStart = Carbon::parse('2026-04-08 00:00:00');
        $windowEnd = Carbon::parse('2026-04-10 00:00:00');

        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->whereBetween('ended_at', [$windowStart, $windowEnd]);

            if ($academyId) {
                $query->where('academy_id', $academyId);
            }

            $total = $query->count();
            $noAttendance = (clone $query)->whereDoesntHave('meetingAttendances')->count();
            $nullTeacher = (clone $query)->whereNull('teacher_attendance_status')->count();
            $nullCounts = (clone $query)->whereNull('counts_for_teacher')->count();

            // Missing earnings
            $sessionIds = (clone $query)->when($type === 'quran', fn ($q) => $q->where('session_type', '!=', 'trial'))->pluck('id')->toArray();
            $existingEarnings = ! empty($sessionIds)
                ? TeacherEarning::withoutGlobalScopes()->where('session_type', $class)->whereIn('session_id', $sessionIds)->count()
                : 0;
            $missingEarnings = count($sessionIds) - $existingEarnings;

            $this->table(
                [ucfirst($type).' Sessions (Apr 8-9)', 'Count'],
                [
                    ['Total completed', $total],
                    ['No MeetingAttendance', $noAttendance],
                    ['NULL teacher_attendance_status', $nullTeacher],
                    ['NULL counts_for_teacher', $nullCounts],
                    ['Missing TeacherEarning', max(0, $missingEarnings)],
                ]
            );
        }

        // Also check stuck ONGOING in window
        $stuckCount = 0;
        foreach ($this->sessionClasses as $type => $class) {
            $query = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::ONGOING)
                ->whereBetween('scheduled_at', [$windowStart, $windowEnd]);
            if ($academyId) {
                $query->where('academy_id', $academyId);
            }
            $stuckCount += $query->count();
        }
        $this->line("Stuck ONGOING in window: {$stuckCount}");
    }
}
