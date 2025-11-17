<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes the individual Quran circles system and all related data.
     * It will delete:
     * - Individual Quran circles
     * - Sessions linked to individual circles
     * - All related attendance records
     * - Student reports
     * - Progress records
     * - Homework records
     * - Subscriptions linked to individual circles
     */
    public function up(): void
    {
        DB::transaction(function () {
            Log::info('Starting removal of individual Quran circles system');

            // Step 1: Get all individual circle IDs (including soft-deleted)
            $individualCircleIds = DB::table('quran_individual_circles')
                ->pluck('id')
                ->toArray();

            if (empty($individualCircleIds)) {
                Log::info('No individual circles found to delete');
                return;
            }

            Log::info('Found ' . count($individualCircleIds) . ' individual circles to remove');

            // Step 2: Get all session IDs linked to individual circles
            $sessionIds = DB::table('quran_sessions')
                ->whereIn('individual_circle_id', $individualCircleIds)
                ->pluck('id')
                ->toArray();

            Log::info('Found ' . count($sessionIds) . ' sessions linked to individual circles');

            // Step 3: Delete related data in correct order (respecting foreign key constraints)

            // 3.1: Delete homework submissions (polymorphic)
            if (!empty($sessionIds)) {
                $homeworkSubmissionsCount = DB::table('homework_submissions')
                    ->where('submitable_type', 'App\\Models\\QuranSession')
                    ->whereIn('submitable_id', $sessionIds)
                    ->delete();
                Log::info("Deleted {$homeworkSubmissionsCount} homework submissions");
            }

            // 3.2: Delete quran homework assignments
            if (!empty($sessionIds)) {
                $homeworkAssignmentsCount = DB::table('quran_homework_assignments')
                    ->whereIn('session_id', $sessionIds)
                    ->delete();
                Log::info("Deleted {$homeworkAssignmentsCount} homework assignments");
            }

            // 3.3: Delete quran session homework
            if (!empty($sessionIds)) {
                $sessionHomeworkCount = DB::table('quran_session_homeworks')
                    ->whereIn('session_id', $sessionIds)
                    ->delete();
                Log::info("Deleted {$sessionHomeworkCount} session homework records");
            }

            // 3.4: Delete session attendances
            if (!empty($sessionIds)) {
                $attendancesCount = DB::table('quran_session_attendances')
                    ->whereIn('session_id', $sessionIds)
                    ->delete();
                Log::info("Deleted {$attendancesCount} session attendance records");
            }

            // 3.5: Delete student session reports
            if (!empty($sessionIds)) {
                $reportsCount = DB::table('student_session_reports')
                    ->whereIn('session_id', $sessionIds)
                    ->delete();
                Log::info("Deleted {$reportsCount} student session reports");
            }

            // 3.6: Delete meeting attendances (if they exist)
            if (!empty($sessionIds)) {
                try {
                    // Meeting attendances are linked polymorphically
                    $meetingAttendancesCount = DB::table('meeting_attendances')
                        ->where('attendanceable_type', 'App\\Models\\QuranSession')
                        ->whereIn('attendanceable_id', $sessionIds)
                        ->delete();
                    Log::info("Deleted {$meetingAttendancesCount} meeting attendance records");
                } catch (\Exception $e) {
                    Log::warning("Could not delete meeting attendances: " . $e->getMessage());
                }
            }

            // 3.7: Delete quran progress records linked to individual circles
            $progressCount = DB::table('quran_progress')
                ->whereIn('circle_id', $individualCircleIds)
                ->delete();
            Log::info("Deleted {$progressCount} progress records");

            // 3.8: Delete quran homework linked to individual circles
            $homeworkCount = DB::table('quran_homework')
                ->whereIn('circle_id', $individualCircleIds)
                ->delete();
            Log::info("Deleted {$homeworkCount} homework records");

            // Step 4: Delete the sessions themselves
            if (!empty($sessionIds)) {
                $sessionsCount = DB::table('quran_sessions')
                    ->whereIn('id', $sessionIds)
                    ->delete();
                Log::info("Deleted {$sessionsCount} Quran sessions");
            }

            // Step 5: Delete subscriptions linked to individual circles
            // Note: We're deleting these because they're specifically for individual circles
            $subscriptionIds = DB::table('quran_subscriptions')
                ->whereIn('id', function ($query) use ($individualCircleIds) {
                    $query->select('subscription_id')
                        ->from('quran_individual_circles')
                        ->whereIn('id', $individualCircleIds)
                        ->whereNotNull('subscription_id');
                })
                ->pluck('id')
                ->toArray();

            if (!empty($subscriptionIds)) {
                $subscriptionsCount = DB::table('quran_subscriptions')
                    ->whereIn('id', $subscriptionIds)
                    ->delete();
                Log::info("Deleted {$subscriptionsCount} subscriptions");
            }

            // Step 6: Finally, delete the individual circles themselves
            $circlesCount = DB::table('quran_individual_circles')
                ->whereIn('id', $individualCircleIds)
                ->delete();
            Log::info("Deleted {$circlesCount} individual Quran circles");

            Log::info('Successfully removed individual Quran circles system');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration is destructive and cannot be fully reversed.
     * The down() method is intentionally empty as we cannot restore deleted data.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it permanently deletes data
        // If you need to restore this functionality, you would need to:
        // 1. Restore data from backups
        // 2. Re-enable the individual circles feature in the application

        Log::warning('Attempted to rollback individual circles removal migration - this operation cannot be reversed');
    }
};
