<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify Interactive Session Reports Table
 *
 * This migration removes unnecessary fields and unifies the report structure
 * with Academic Session Reports.
 *
 * REMOVED FIELDS:
 * - quiz_score: Over-complicated for live session reports
 * - video_completion_percentage: Not relevant for live sessions
 * - exercises_completed: Not relevant for live sessions
 * - engagement_score: Redundant with overall performance
 *
 * ADDED FIELDS:
 * - homework_degree (0-10): Unified field matching Academic reports
 *
 * KEPT FIELDS:
 * - All base attendance fields
 * - notes (teacher notes)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interactive_session_reports', function (Blueprint $table) {
            // Add homework_degree field (unified with Academic reports)
            $table->decimal('homework_degree', 3, 1)->nullable()
                ->after('notes')
                ->comment('درجة أداء الواجب من 0 إلى 10');

            // Remove unnecessary fields
            $table->dropColumn([
                'quiz_score',
                'video_completion_percentage',
                'exercises_completed',
                'engagement_score',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_session_reports', function (Blueprint $table) {
            // Re-add removed fields
            $table->decimal('quiz_score', 5, 2)->nullable();
            $table->decimal('video_completion_percentage', 5, 2)->default(0);
            $table->integer('exercises_completed')->default(0);
            $table->decimal('engagement_score', 3, 1)->nullable();

            // Remove added field
            $table->dropColumn('homework_degree');
        });
    }
};
