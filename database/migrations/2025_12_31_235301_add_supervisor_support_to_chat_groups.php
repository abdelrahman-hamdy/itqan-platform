<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            // Add supervisor reference for supervised chats
            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->after('owner_id')
                  ->constrained('users')
                  ->nullOnDelete();

            // Add individual circle/lesson references for supervised individual chats
            $table->foreignId('quran_individual_circle_id')
                  ->nullable()
                  ->after('recorded_course_id')
                  ->constrained('quran_individual_circles')
                  ->nullOnDelete();

            $table->foreignId('academic_individual_lesson_id')
                  ->nullable()
                  ->after('quran_individual_circle_id')
                  ->constrained('academic_individual_lessons')
                  ->nullOnDelete();

            // Add metadata column if it doesn't exist (for group context info)
            if (!Schema::hasColumn('chat_groups', 'metadata')) {
                $table->json('metadata')->nullable()->after('settings');
            }

            // Add soft deletes if not exists
            if (!Schema::hasColumn('chat_groups', 'deleted_at')) {
                $table->softDeletes();
            }

            // Index for efficient supervisor lookups
            $table->index(['supervisor_id', 'is_active'], 'chat_groups_supervisor_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_groups', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('chat_groups_supervisor_active_idx');

            // Drop foreign keys and columns
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');

            $table->dropForeign(['quran_individual_circle_id']);
            $table->dropColumn('quran_individual_circle_id');

            $table->dropForeign(['academic_individual_lesson_id']);
            $table->dropColumn('academic_individual_lesson_id');

            // Only drop if we added them
            if (Schema::hasColumn('chat_groups', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('chat_groups', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
