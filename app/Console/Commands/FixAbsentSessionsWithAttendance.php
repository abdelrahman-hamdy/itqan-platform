<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionEarningsJob;
use App\Models\QuranSession;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * LEGACY DATA COMMAND: This command is for fixing historical data from when
 * SessionStatus::ABSENT existed. The ABSENT session status has been removed;
 * sessions now always complete to COMPLETED status with attendance tracked
 * separately via attendance_status. This command fixes any remaining legacy
 * ABSENT sessions in the database.
 */
class FixAbsentSessionsWithAttendance extends Command
{
    protected $signature = 'sessions:fix-absent-with-attendance
                          {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Legacy: Fix sessions marked ABSENT that have actual meeting attendance for both teacher and student';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Finding absent sessions with meeting attendance...');

        // Find absent sessions where both teacher AND student have meeting attendance
        // Using raw query since the meetingAttendances relationship had the session_type mismatch
        $candidates = DB::select("
            SELECT qs.id, qs.title, qs.scheduled_at, qs.duration_minutes, qs.started_at,
                   qs.quran_teacher_id, qs.student_id,
                   MAX(CASE WHEN ma.user_type = 'teacher' THEN ma.total_duration_minutes END) as teacher_min,
                   MAX(CASE WHEN ma.user_type = 'student' THEN ma.total_duration_minutes END) as student_min,
                   MIN(CASE WHEN ma.user_type = 'teacher' THEN ma.first_join_time END) as teacher_first_join,
                   MAX(ma.last_leave_time) as last_leave
            FROM quran_sessions qs
            JOIN meeting_attendances ma ON ma.session_id = qs.id AND ma.session_type IN ('individual', 'group')
            WHERE qs.status = ?
            AND qs.deleted_at IS NULL
            GROUP BY qs.id, qs.title, qs.scheduled_at, qs.duration_minutes, qs.started_at,
                     qs.quran_teacher_id, qs.student_id
            HAVING teacher_min > 0 AND student_min > 0
        ", ['absent']);

        if (empty($candidates)) {
            $this->info('No absent sessions found with both teacher and student attendance.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($candidates).' session(s) to fix:');
        $this->newLine();

        $fixed = 0;

        foreach ($candidates as $c) {
            $this->line("Session #{$c->id}: {$c->title}");
            $this->line("  Teacher (ID:{$c->quran_teacher_id}): {$c->teacher_min} min");
            $this->line("  Student (ID:{$c->student_id}): {$c->student_min} min");
            $this->line('  started_at: '.($c->started_at ?? 'NULL'));

            if ($isDryRun) {
                $this->info('  -> Would fix to COMPLETED');
                $this->line('---');
                $fixed++;

                continue;
            }

            try {
                DB::transaction(function () use ($c) {
                    $session = QuranSession::withoutGlobalScopes()->lockForUpdate()->find($c->id);
                    if (! $session || ($session->status instanceof SessionStatus ? $session->status->value : $session->status) !== 'absent') {
                        return;
                    }

                    $updateData = [
                        'status' => SessionStatus::COMPLETED,
                        'attendance_status' => AttendanceStatus::ATTENDED->value,
                    ];

                    // Set started_at from teacher's first join if missing
                    if (! $session->started_at && $c->teacher_first_join) {
                        $updateData['started_at'] = $c->teacher_first_join;
                    }

                    // Set ended_at from last leave time
                    if ($c->last_leave) {
                        $updateData['ended_at'] = $c->last_leave;
                    }

                    // Calculate actual duration
                    $actualMinutes = max($c->teacher_min, $c->student_min);
                    $updateData['actual_duration_minutes'] = $actualMinutes;

                    $session->updateQuietly($updateData);

                    // Dispatch earnings calculation
                    dispatch(new CalculateSessionEarningsJob($session));
                });

                $this->info('  -> Fixed to COMPLETED + earnings dispatched');
                $fixed++;

            } catch (Exception $e) {
                $this->error("  -> Error: {$e->getMessage()}");
            }

            $this->line('---');
        }

        $this->newLine();
        $this->info("Summary: {$fixed} session(s) ".($isDryRun ? 'would be' : '').' fixed');

        return self::SUCCESS;
    }
}
