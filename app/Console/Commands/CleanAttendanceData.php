<?php

namespace App\Console\Commands;

use App\Models\MeetingAttendance;
use App\Models\MeetingAttendanceEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clean Old Attendance Data
 *
 * Removes old attendance records after system migration.
 */
class CleanAttendanceData extends Command
{
    protected $signature = 'attendance:clean
                          {--all : Delete ALL attendance data}
                          {--before= : Delete data before this date (Y-m-d)}
                          {--dry-run : Show what would be deleted without actually deleting}
                          {--force : Skip confirmation prompt}';

    protected $description = 'Clean old attendance data from tables';

    /**
     * Hide this command in production - one-time cleanup only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    public function handle(): int
    {
        $all = $this->option('all');
        $before = $this->option('before');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (! $all && ! $before) {
            $this->error('❌ Must specify --all or --before=DATE');
            $this->info('Examples:');
            $this->line('  php artisan attendance:clean --all --dry-run');
            $this->line('  php artisan attendance:clean --before=2025-11-01');
            $this->line('  php artisan attendance:clean --before=2025-11-14 --force');

            return 1;
        }

        // Get counts before deletion
        $this->info('📊 Current Database Status:');
        $this->newLine();

        $eventsQuery = MeetingAttendanceEvent::query();
        $attendanceQuery = MeetingAttendance::query();

        if ($all) {
            $this->warn('⚠️  You are about to delete ALL attendance data');
        } elseif ($before) {
            $date = \Carbon\Carbon::parse($before);
            $eventsQuery->where('created_at', '<', $date);
            $attendanceQuery->where('created_at', '<', $date);
            $this->info("Targeting data created before: {$date->toDateString()}");
        }

        $eventsCount = $eventsQuery->count();
        $attendanceCount = $attendanceQuery->count();

        $this->table(
            ['Table', 'Records to Delete'],
            [
                ['meeting_attendance_events', number_format($eventsCount)],
                ['meeting_attendances', number_format($attendanceCount)],
            ]
        );

        if ($eventsCount === 0 && $attendanceCount === 0) {
            $this->info('✅ No records to delete');

            return 0;
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('🔍 DRY RUN - No data will be deleted');
            $this->info("Would delete {$eventsCount} event records");
            $this->info("Would delete {$attendanceCount} attendance records");

            return 0;
        }

        // Confirmation
        if (! $force) {
            $this->newLine();
            $this->warn('⚠️  This action cannot be undone!');

            if (! $this->confirm('Do you want to proceed with deletion?', false)) {
                $this->info('❌ Operation cancelled');

                return 0;
            }

            // Double confirmation for --all
            if ($all) {
                if (! $this->confirm('Are you ABSOLUTELY sure? This will delete ALL attendance data!', false)) {
                    $this->info('❌ Operation cancelled');

                    return 0;
                }
            }
        }

        // Perform deletion
        $this->newLine();
        $this->info('🗑️  Deleting attendance data...');

        $bar = $this->output->createProgressBar(2);
        $bar->start();

        DB::beginTransaction();

        try {
            // Delete events
            $eventsDeleted = $eventsQuery->delete();
            $bar->advance();

            // Delete attendance records
            $attendanceDeleted = $attendanceQuery->delete();
            $bar->advance();

            DB::commit();

            $bar->finish();
            $this->newLine(2);

            // Show results
            $this->info('✅ Deletion completed successfully!');
            $this->newLine();
            $this->table(
                ['Table', 'Records Deleted'],
                [
                    ['meeting_attendance_events', number_format($eventsDeleted)],
                    ['meeting_attendances', number_format($attendanceDeleted)],
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $bar->finish();
            $this->newLine(2);
            $this->error('❌ Deletion failed: '.$e->getMessage());

            return 1;
        }
    }
}
