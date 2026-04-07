<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Services\SessionSettingsService;
use App\Services\Traits\AttendanceCalculatorTrait;
use Illuminate\Console\Command;

/**
 * Backfill teacher_attendance_status on completed sessions from meeting_attendances data.
 */
class BackfillTeacherAttendance extends Command
{
    use AttendanceCalculatorTrait;

    protected $signature = 'attendance:backfill-teacher {--dry-run : Show what would be done without updating}';

    protected $description = 'Backfill teacher_attendance_status and counts_for_teacher on completed sessions from meeting_attendances';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $settingsService = app(SessionSettingsService::class);

        $sessionClasses = [
            'quran' => QuranSession::class,
            'academic' => AcademicSession::class,
            'interactive' => InteractiveCourseSession::class,
        ];

        $totalUpdated = 0;

        foreach ($sessionClasses as $type => $class) {
            $sessions = $class::withoutGlobalScopes()
                ->where('status', SessionStatus::COMPLETED)
                ->whereNull('teacher_attendance_status')
                ->get();

            $this->info("Processing {$sessions->count()} {$type} sessions...");

            foreach ($sessions as $session) {
                $teacherAtt = MeetingAttendance::where('session_id', $session->id)
                    ->where('session_type', $type === 'quran' ? 'individual' : $type)
                    ->whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])
                    ->where('is_calculated', true)
                    ->first();

                // Also try without session_type filter (legacy data may not have correct type)
                if (! $teacherAtt) {
                    $teacherAtt = MeetingAttendance::where('session_id', $session->id)
                        ->whereIn('user_type', ['teacher', 'quran_teacher', 'academic_teacher'])
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
                    $this->line("  Session {$session->id}: teacher={$teacherStatus->value}, counts={$teacherCounts}, duration=" . ($teacherAtt?->total_duration_minutes ?? 0) . "min");
                } else {
                    $updateData = [
                        'teacher_attendance_status' => $teacherStatus->value,
                        'teacher_attendance_calculated_at' => now(),
                    ];

                    // Only set counts_for_teacher if not already overridden by admin
                    if ($session->counts_for_teacher_set_by === null) {
                        $updateData['counts_for_teacher'] = $teacherCounts;
                    }

                    $session->update($updateData);
                }

                $totalUpdated++;
            }
        }

        $verb = $isDryRun ? 'Would update' : 'Updated';
        $this->info("{$verb} {$totalUpdated} sessions.");

        return self::SUCCESS;
    }
}
