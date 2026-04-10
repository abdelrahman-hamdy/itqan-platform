<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Recalculate denormalized counters on teacher profiles (total_students, total_sessions).
 *
 * total_students and total_sessions are excluded from the model's $fillable for security,
 * and no observer currently maintains them. This command provides a safety net; runs
 * every 15 minutes via the scheduler.
 */
class RecalculateTeacherProfileCounters extends Command
{
    protected $signature = 'teachers:recalculate-counters
                            {--teacher= : Specific teacher user_id (optional)}
                            {--type=all : quran | academic | all}';

    protected $description = 'Recalculate total_students and total_sessions on teacher profiles from actual session data';

    public function handle(): int
    {
        $type = $this->option('type');
        $teacherId = $this->option('teacher');

        $total = 0;

        if (in_array($type, ['quran', 'all'], true)) {
            $total += $this->recalculateQuranProfiles($teacherId);
        }

        if (in_array($type, ['academic', 'all'], true)) {
            $total += $this->recalculateAcademicProfiles($teacherId);
        }

        $this->info("Done. Updated {$total} teacher profiles.");

        return self::SUCCESS;
    }

    private function recalculateQuranProfiles(?string $teacherId): int
    {
        $profileQuery = QuranTeacherProfile::query();
        if ($teacherId) {
            $profileQuery->where('user_id', $teacherId);
        }
        $profiles = $profileQuery->get(['id', 'user_id', 'total_students', 'total_sessions']);
        if ($profiles->isEmpty()) {
            return 0;
        }

        $this->info("Recalculating {$profiles->count()} Quran teacher profiles...");

        $stats = $this->aggregateSessionStats(
            QuranSession::query()->whereIn('quran_teacher_id', $profiles->pluck('user_id')),
            'quran_teacher_id',
        );

        return $this->applyStats(
            'quran_teacher_profiles',
            $profiles,
            fn ($profile) => $stats->get($profile->user_id),
            hasSessionsCounter: true,
        );
    }

    private function recalculateAcademicProfiles(?string $teacherId): int
    {
        $profileQuery = AcademicTeacherProfile::query();
        if ($teacherId) {
            $profileQuery->where('user_id', $teacherId);
        }
        $profiles = $profileQuery->get(['id', 'user_id', 'total_students']);
        if ($profiles->isEmpty()) {
            return 0;
        }

        $this->info("Recalculating {$profiles->count()} Academic teacher profiles...");

        // AcademicSession.academic_teacher_id references academic_teacher_profiles.id (not user_id)
        $stats = $this->aggregateSessionStats(
            AcademicSession::query()->whereIn('academic_teacher_id', $profiles->pluck('id')),
            'academic_teacher_id',
        );

        return $this->applyStats(
            'academic_teacher_profiles',
            $profiles,
            fn ($profile) => $stats->get($profile->id),
            hasSessionsCounter: false,
        );
    }

    /**
     * Single aggregate query grouped by teacher_id column.
     */
    private function aggregateSessionStats(Builder $query, string $teacherColumn): Collection
    {
        return $query
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->groupBy($teacherColumn)
            ->selectRaw("{$teacherColumn} AS teacher_key, COUNT(*) AS total_sessions, COUNT(DISTINCT student_id) AS total_students")
            ->get()
            ->keyBy('teacher_key');
    }

    /**
     * Compare cached counters against aggregated stats and apply diffs only.
     */
    private function applyStats(
        string $table,
        Collection $profiles,
        callable $resolveStat,
        bool $hasSessionsCounter,
    ): int {
        $updated = 0;

        foreach ($profiles as $profile) {
            $stat = $resolveStat($profile);
            $newSessions = (int) ($stat->total_sessions ?? 0);
            $newStudents = (int) ($stat->total_students ?? 0);

            $payload = ['total_students' => $newStudents];
            $changed = $profile->total_students !== $newStudents;

            if ($hasSessionsCounter) {
                $payload['total_sessions'] = $newSessions;
                $changed = $changed || $profile->total_sessions !== $newSessions;
            }

            if (! $changed) {
                continue;
            }

            DB::table($table)->where('id', $profile->id)->update($payload);
            $updated++;
        }

        return $updated;
    }
}
