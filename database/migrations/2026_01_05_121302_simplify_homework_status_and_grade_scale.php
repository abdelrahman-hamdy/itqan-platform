<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify Homework Status and Grade Scale
 *
 * This migration:
 * 1. Simplifies statuses from 7 to 4: pending, submitted, late, graded
 * 2. Standardizes grade scale to 0-10 (from 0-100)
 * 3. Adds max_score column to interactive_course_homework
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add max_score column to interactive_course_homework if missing
        if (! Schema::hasColumn('interactive_course_homework', 'max_score')) {
            Schema::table('interactive_course_homework', function (Blueprint $table) {
                $table->decimal('max_score', 5, 2)->default(10)->after('score');
            });
        }

        // Step 2: Convert old status values to new simplified statuses
        // For academic_homework_submissions
        DB::table('academic_homework_submissions')
            ->whereIn('submission_status', ['not_started', 'draft'])
            ->update(['submission_status' => 'pending']);

        DB::table('academic_homework_submissions')
            ->whereIn('submission_status', ['returned', 'resubmitted'])
            ->update(['submission_status' => 'submitted']);

        // For interactive_course_homework
        DB::table('interactive_course_homework')
            ->where('submission_status', 'not_submitted')
            ->update(['submission_status' => 'pending']);

        DB::table('interactive_course_homework')
            ->where('submission_status', 'returned')
            ->update(['submission_status' => 'submitted']);

        // Step 3: Convert scores from 0-100 scale to 0-10 scale
        // Only for records that have max_score of 100 (the old default)
        DB::table('academic_homework_submissions')
            ->where('max_score', 100)
            ->whereNotNull('score')
            ->update([
                'score' => DB::raw('score / 10'),
                'max_score' => 10,
            ]);

        // Set max_score to 10 for any NULL or 100 values
        DB::table('academic_homework_submissions')
            ->where(function ($query) {
                $query->whereNull('max_score')
                    ->orWhere('max_score', 100);
            })
            ->update(['max_score' => 10]);

        // For interactive_course_homework - handle scores based on session's homework_max_score
        // If score > 10, it was on old 0-100 scale, convert it
        DB::table('interactive_course_homework')
            ->where('score', '>', 10)
            ->update([
                'score' => DB::raw('score / 10'),
                'max_score' => 10,
            ]);

        // Set max_score to 10 for all interactive course homework
        DB::table('interactive_course_homework')
            ->update(['max_score' => 10]);
    }

    public function down(): void
    {
        // Note: This is a data migration, reversing it fully is not possible
        // The status values before were: not_started, draft, submitted, late, graded, returned, resubmitted
        // We cannot know which 'pending' was 'not_started' vs 'draft'
        // We cannot know which 'submitted' was 'returned' vs 'resubmitted'
        //
        // For scores, we could multiply by 10, but that assumes all were on 0-100 scale originally
        // which may not be true for new records created after this migration

        // Revert interactive_course_homework status (best effort)
        DB::table('interactive_course_homework')
            ->where('submission_status', 'pending')
            ->update(['submission_status' => 'not_submitted']);
    }
};
