<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\TeacherEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Export all attendance-related data as JSON snapshots for reference before repairs.
 */
class BackupAttendanceData extends Command
{
    protected $signature = 'attendance:backup
                          {--path=storage/backups/attendance : Directory to write backup files}';

    protected $description = 'Export attendance, sessions, earnings, and reports data as JSON backup files';

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $this->info("Backing up attendance data to: {$path}");
        $timestamp = now()->toIso8601String();

        // 1. Quran sessions — attendance-relevant fields
        $this->exportTable($path, 'quran_sessions_backup.json', $timestamp, function () {
            return QuranSession::withoutGlobalScopes()
                ->select([
                    'id', 'academy_id', 'status', 'session_type', 'scheduled_at', 'started_at',
                    'ended_at', 'duration_minutes', 'actual_duration_minutes',
                    'teacher_attendance_status', 'teacher_attendance_calculated_at',
                    'counts_for_teacher', 'counts_for_teacher_set_by', 'counts_for_teacher_set_at',
                    'subscription_counted', 'quran_teacher_id', 'student_id', 'circle_id',
                    'attendance_status', 'participants_count',
                ]);
        });

        // 2. Academic sessions
        $this->exportTable($path, 'academic_sessions_backup.json', $timestamp, function () {
            return AcademicSession::withoutGlobalScopes()
                ->select([
                    'id', 'academy_id', 'status', 'session_type', 'scheduled_at', 'started_at',
                    'ended_at', 'duration_minutes', 'actual_duration_minutes',
                    'teacher_attendance_status', 'teacher_attendance_calculated_at',
                    'counts_for_teacher', 'counts_for_teacher_set_by', 'counts_for_teacher_set_at',
                    'subscription_counted', 'academic_teacher_id', 'student_id',
                    'attendance_status', 'participants_count',
                ]);
        });

        // 3. Interactive course sessions
        $this->exportTable($path, 'interactive_sessions_backup.json', $timestamp, function () {
            return InteractiveCourseSession::withoutGlobalScopes()
                ->select([
                    'id', 'academy_id', 'course_id', 'status', 'session_number',
                    'scheduled_at', 'started_at', 'ended_at',
                    'duration_minutes', 'actual_duration_minutes',
                    'teacher_attendance_status', 'teacher_attendance_calculated_at',
                    'counts_for_teacher', 'counts_for_teacher_set_by', 'counts_for_teacher_set_at',
                    'attendance_status', 'participants_count', 'attendance_count',
                ]);
        });

        // 4. Meeting attendances (full table)
        $this->exportTable($path, 'meeting_attendances_backup.json', $timestamp, function () {
            return MeetingAttendance::withoutGlobalScopes()
                ->select([
                    'id', 'session_id', 'user_id', 'user_type', 'session_type',
                    'first_join_time', 'last_leave_time', 'total_duration_minutes',
                    'join_leave_cycles', 'attendance_calculated_at',
                    'attendance_status', 'attendance_percentage',
                    'session_duration_minutes', 'session_start_time', 'session_end_time',
                    'join_count', 'leave_count', 'is_calculated',
                    'counts_for_subscription', 'counts_for_subscription_set_by',
                    'counts_for_subscription_set_at',
                    'created_at', 'updated_at',
                ]);
        });

        // 5. Teacher earnings (full table)
        $this->exportTable($path, 'teacher_earnings_backup.json', $timestamp, function () {
            return TeacherEarning::withoutGlobalScopes()
                ->select([
                    'id', 'academy_id', 'teacher_type', 'teacher_id',
                    'session_type', 'session_id', 'amount', 'calculation_method',
                    'rate_snapshot', 'calculation_metadata',
                    'earning_month', 'session_completed_at', 'calculated_at',
                    'is_finalized', 'is_disputed', 'dispute_notes',
                    'created_at', 'updated_at',
                ]);
        });

        // 6. Student session reports (Quran)
        $this->exportTable($path, 'student_session_reports_backup.json', $timestamp, function () {
            return StudentSessionReport::withoutGlobalScopes()
                ->select([
                    'id', 'session_id', 'student_id', 'teacher_id', 'academy_id',
                    'attendance_status', 'meeting_enter_time', 'meeting_leave_time',
                    'actual_attendance_minutes', 'attendance_percentage',
                    'is_late', 'late_minutes', 'is_calculated',
                    'created_at', 'updated_at',
                ]);
        });

        // 7. Academic session reports
        $this->exportTable($path, 'academic_session_reports_backup.json', $timestamp, function () {
            return AcademicSessionReport::withoutGlobalScopes()
                ->select([
                    'id', 'session_id', 'student_id', 'teacher_id', 'academy_id',
                    'attendance_status', 'meeting_enter_time', 'meeting_leave_time',
                    'actual_attendance_minutes', 'attendance_percentage',
                    'is_late', 'late_minutes', 'is_calculated',
                    'created_at', 'updated_at',
                ]);
        });

        // 8. Interactive session reports
        $this->exportTable($path, 'interactive_session_reports_backup.json', $timestamp, function () {
            return InteractiveSessionReport::withoutGlobalScopes()
                ->select([
                    'id', 'session_id', 'student_id', 'academy_id',
                    'attendance_status', 'meeting_enter_time', 'meeting_leave_time',
                    'actual_attendance_minutes', 'attendance_percentage',
                    'is_late', 'late_minutes', 'is_calculated',
                    'created_at', 'updated_at',
                ]);
        });

        $this->newLine();
        $this->info('Backup complete.');

        return self::SUCCESS;
    }

    private function exportTable(string $path, string $filename, string $timestamp, callable $queryBuilder): void
    {
        $this->output->write("  Exporting {$filename}... ");

        $filePath = $path.'/'.$filename;
        $count = 0;

        // Stream JSON to file in chunks to avoid OOM on large tables
        $handle = fopen($filePath, 'w');
        fwrite($handle, '{"exported_at":"'.$timestamp.'","records":[');

        $first = true;
        $queryBuilder()->chunk(2000, function ($chunk) use ($handle, &$count, &$first) {
            foreach ($chunk as $record) {
                if (! $first) {
                    fwrite($handle, ',');
                }
                fwrite($handle, json_encode($record, JSON_UNESCAPED_UNICODE));
                $first = false;
                $count++;
            }
        });

        fwrite($handle, '],"record_count":'.$count.'}');
        fclose($handle);

        $this->output->writeln("<info>{$count} records</info>");
    }
}
