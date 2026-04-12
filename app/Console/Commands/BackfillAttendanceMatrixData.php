<?php

namespace App\Console\Commands;

use App\Models\MeetingAttendance;
use App\Services\SessionSettingsService;
use App\Services\Traits\AttendanceCalculatorTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill historical meeting_attendances with display_duration_minutes
 * and counts_for_subscription / counts_for_teacher flags introduced by
 * the percentage-based attendance refactor.
 *
 * Safe to re-run: only touches rows where the target fields are still NULL/0.
 * Honors admin overrides (counts_for_subscription_set_by IS NOT NULL → skip).
 *
 * Usage:
 *   php artisan attendance:backfill-matrix --dry-run      # preview only
 *   php artisan attendance:backfill-matrix                 # execute
 *   php artisan attendance:backfill-matrix --chunk=50      # smaller batches
 */
class BackfillAttendanceMatrixData extends Command
{
    use AttendanceCalculatorTrait;

    protected $signature = 'attendance:backfill-matrix
        {--dry-run : Preview changes without writing}
        {--chunk=100 : Batch size for processing}';

    protected $description = 'Backfill display_duration_minutes and matrix counting flags on historical meeting_attendances';

    private int $updatedDisplay = 0;

    private int $updatedMatrix = 0;

    private int $skippedAdmin = 0;

    private int $skippedAlready = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        $this->info($dryRun ? '🔍 DRY RUN — no data will be modified' : '⚡ LIVE RUN — data will be updated');
        $this->newLine();

        // Phase 1: Backfill display_duration_minutes on calculated rows where it's 0
        $this->info('Phase 1: Backfill display_duration_minutes...');
        $this->backfillDisplayDuration($dryRun, $chunk);

        // Phase 2: Backfill counts_for_subscription on calculated student rows where it's NULL
        $this->info('Phase 2: Backfill counts_for_subscription (matrix flags)...');
        $this->backfillMatrixFlags($dryRun, $chunk);

        // Phase 3: Backfill counts_for_teacher on completed sessions where it's NULL
        $this->info('Phase 3: Backfill counts_for_teacher on sessions...');
        $this->backfillCountsForTeacher($dryRun, $chunk);

        // Summary
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['display_duration updated', $this->updatedDisplay],
                ['matrix flags updated', $this->updatedMatrix],
                ['skipped (admin override)', $this->skippedAdmin],
                ['skipped (already set)', $this->skippedAlready],
                ['errors', $this->errors],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN complete. Run without --dry-run to apply changes.');
        } else {
            $this->info('Backfill complete.');
        }

        return self::SUCCESS;
    }

    /**
     * Phase 1: For each calculated MeetingAttendance with display_duration_minutes = 0,
     * copy total_duration_minutes into it (historical rows don't have uncapped data,
     * so capped = uncapped for them).
     */
    private function backfillDisplayDuration(bool $dryRun, int $chunk): void
    {
        if (! \Schema::hasColumn('meeting_attendances', 'display_duration_minutes')) {
            $this->warn('  Column display_duration_minutes does not exist yet. Run migrations first.');

            return;
        }

        $query = MeetingAttendance::where('is_calculated', true)
            ->where('display_duration_minutes', 0)
            ->where('total_duration_minutes', '>', 0);

        $total = $query->count();
        $this->line("  Found {$total} rows to backfill");

        if ($total === 0 || $dryRun) {
            $this->updatedDisplay = $total;

            return;
        }

        // Single bulk UPDATE — no row-by-row needed since it's a direct copy
        $affected = DB::table('meeting_attendances')
            ->where('is_calculated', true)
            ->where('display_duration_minutes', 0)
            ->where('total_duration_minutes', '>', 0)
            ->update(['display_duration_minutes' => DB::raw('total_duration_minutes')]);

        $this->updatedDisplay = $affected;
        $this->line("  Updated {$affected} rows");
    }

    /**
     * Phase 2: For each calculated student MeetingAttendance with NULL counts_for_subscription,
     * apply the matrix rules:
     *   - Teacher present + student present → count both
     *   - Both absent → count for student, not teacher
     *   - Student present + teacher absent → count neither
     *   - Teacher present + student absent → count both
     */
    private function backfillMatrixFlags(bool $dryRun, int $chunk): void
    {
        $settingsService = app(SessionSettingsService::class);

        $query = MeetingAttendance::where('is_calculated', true)
            ->where('user_type', 'student')
            ->whereNull('counts_for_subscription')
            ->whereNull('counts_for_subscription_set_by');

        $total = $query->count();
        $this->line("  Found {$total} student rows with NULL counts_for_subscription");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->with(['session'])->chunkById($chunk, function ($attendances) use ($dryRun, $settingsService, $bar) {
            foreach ($attendances as $attendance) {
                $bar->advance();

                $session = $attendance->session;
                if (! $session) {
                    $this->errors++;

                    continue;
                }

                // Find teacher attendance for this session
                $teacherAtt = MeetingAttendance::where('session_id', $attendance->session_id)
                    ->where('session_type', $attendance->session_type)
                    ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
                    ->where('is_calculated', true)
                    ->first();

                // Get teacher thresholds
                try {
                    [$teacherFull, $teacherPartial] = $settingsService
                        ->getAttendanceThresholdsForUserType($session, 'teacher');
                } catch (\Exception $e) {
                    $teacherFull = 90;
                    $teacherPartial = 50;
                }

                // Calculate teacher percentage
                $sessionDuration = $session->duration_minutes ?? 60;
                $teacherMinutes = $teacherAtt?->total_duration_minutes ?? 0;
                $teacherPct = $sessionDuration > 0
                    ? ($teacherMinutes / $sessionDuration) * 100
                    : 0;

                $teacherPresent = $teacherPct >= $teacherPartial;

                // Calculate student percentage
                $studentMinutes = $attendance->total_duration_minutes ?? 0;
                $studentPct = $sessionDuration > 0
                    ? ($studentMinutes / $sessionDuration) * 100
                    : 0;
                $studentPresent = $studentPct > 0;

                // Apply matrix rule
                if ($teacherPresent) {
                    // Rules 1 & 4: teacher present → always count both
                    $countsForSub = true;
                } elseif ($studentPresent) {
                    // Rule 3: student present + teacher absent → count neither
                    $countsForSub = false;
                } else {
                    // Rule 2: both absent → count for student
                    $countsForSub = true;
                }

                if (! $dryRun) {
                    $attendance->update([
                        'counts_for_subscription' => $countsForSub,
                        'counts_for_subscription_set_by' => null,
                        'counts_for_subscription_set_at' => now(),
                    ]);
                }

                $this->updatedMatrix++;
            }
        });

        $bar->finish();
        $this->newLine();
        $this->line("  Processed {$this->updatedMatrix} rows".($dryRun ? ' (dry run)' : ''));
    }

    /**
     * Phase 3: For completed sessions with NULL counts_for_teacher,
     * determine if teacher attended and set the flag.
     */
    private function backfillCountsForTeacher(bool $dryRun, int $chunk): void
    {
        $settingsService = app(SessionSettingsService::class);
        $updatedTeacher = 0;

        foreach (['quran_sessions', 'academic_sessions', 'interactive_course_sessions'] as $table) {
            $sessions = DB::table($table)
                ->where('status', 'completed')
                ->whereNull('counts_for_teacher')
                ->select('id', 'duration_minutes', 'academy_id')
                ->get();

            $this->line("  {$table}: {$sessions->count()} sessions with NULL counts_for_teacher");

            foreach ($sessions as $session) {
                $teacherAtt = MeetingAttendance::where('session_id', $session->id)
                    ->whereIn('user_type', MeetingAttendance::TEACHER_USER_TYPES)
                    ->where('is_calculated', true)
                    ->first();

                $sessionDuration = $session->duration_minutes ?? 60;
                $teacherMinutes = $teacherAtt?->total_duration_minutes ?? 0;
                $teacherPct = $sessionDuration > 0
                    ? ($teacherMinutes / $sessionDuration) * 100
                    : 0;

                // Teacher is "present" if they met the partial threshold
                $countsForTeacher = $teacherPct >= 50; // default partial threshold

                if (! $dryRun) {
                    DB::table($table)
                        ->where('id', $session->id)
                        ->update(['counts_for_teacher' => $countsForTeacher]);
                }

                $updatedTeacher++;
            }
        }

        $this->line("  Updated {$updatedTeacher} session rows".($dryRun ? ' (dry run)' : ''));
    }
}
