<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalculate denormalized counters on teacher profiles (total_students, total_sessions).
 *
 * Background: total_students and total_sessions are excluded from the model's $fillable
 * for security reasons, and no observer or scheduled command currently maintains them.
 * This caused all teachers to see "0 students / 0 sessions" on their dashboards.
 *
 * Runs via schedule every 15 minutes as a safety net. Direct session completion should
 * still increment counters via QuranSessionObserver.
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
        $query = QuranTeacherProfile::query();
        if ($teacherId) {
            $query->where('user_id', $teacherId);
        }

        $profiles = $query->get(['id', 'user_id', 'total_students', 'total_sessions']);
        $this->info("Recalculating {$profiles->count()} Quran teacher profiles...");

        $updated = 0;
        foreach ($profiles as $profile) {
            $stats = QuranSession::where('quran_teacher_id', $profile->user_id)
                ->where('status', 'completed')
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) AS total, COUNT(DISTINCT student_id) AS students')
                ->first();

            $newTotalSessions = (int) ($stats->total ?? 0);
            $newTotalStudents = (int) ($stats->students ?? 0);

            if ($profile->total_sessions === $newTotalSessions && $profile->total_students === $newTotalStudents) {
                continue;
            }

            DB::table('quran_teacher_profiles')
                ->where('id', $profile->id)
                ->update([
                    'total_sessions' => $newTotalSessions,
                    'total_students' => $newTotalStudents,
                ]);

            $updated++;
        }

        return $updated;
    }

    private function recalculateAcademicProfiles(?string $teacherId): int
    {
        $query = AcademicTeacherProfile::query();
        if ($teacherId) {
            $query->where('user_id', $teacherId);
        }

        $profiles = $query->get(['id', 'user_id', 'total_students']);
        $this->info("Recalculating {$profiles->count()} Academic teacher profiles...");

        $updated = 0;
        foreach ($profiles as $profile) {
            $stats = AcademicSession::where('academic_teacher_id', $profile->id)
                ->where('status', 'completed')
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) AS total, COUNT(DISTINCT student_id) AS students')
                ->first();

            $newTotalStudents = (int) ($stats->students ?? 0);

            if ($profile->total_students === $newTotalStudents) {
                continue;
            }

            DB::table('academic_teacher_profiles')
                ->where('id', $profile->id)
                ->update([
                    'total_students' => $newTotalStudents,
                ]);

            $updated++;
        }

        return $updated;
    }
}
