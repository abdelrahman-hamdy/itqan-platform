<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_reviews', function (Blueprint $table) {
            // Add polymorphic columns
            $table->string('reviewable_type')->default('App\\Models\\RecordedCourse')->after('id');

            // Add academy_id for tenant scoping (nullable for existing data)
            $table->foreignId('academy_id')->nullable()->after('reviewable_type');
        });

        // Rename course_id to reviewable_id
        Schema::table('course_reviews', function (Blueprint $table) {
            $table->renameColumn('course_id', 'reviewable_id');
        });

        // Drop the old unique constraint and add new one
        Schema::table('course_reviews', function (Blueprint $table) {
            $table->dropUnique('course_reviews_course_id_user_id_unique'); // Original constraint name
        });

        Schema::table('course_reviews', function (Blueprint $table) {
            // Add new unique constraint for polymorphic
            $table->unique(['reviewable_type', 'reviewable_id', 'user_id'], 'unique_course_review');
            $table->index(['reviewable_type', 'reviewable_id'], 'course_reviews_reviewable_index');
        });

        // Update existing records to set academy_id from related course
        DB::statement("
            UPDATE course_reviews cr
            JOIN recorded_courses rc ON cr.reviewable_id = rc.id
            SET cr.academy_id = rc.academy_id
            WHERE cr.reviewable_type = 'App\\\\Models\\\\RecordedCourse'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_reviews', function (Blueprint $table) {
            $table->dropIndex('course_reviews_reviewable_index');
            $table->dropUnique('unique_course_review');
        });

        Schema::table('course_reviews', function (Blueprint $table) {
            $table->renameColumn('reviewable_id', 'course_id');
        });

        Schema::table('course_reviews', function (Blueprint $table) {
            $table->dropColumn(['reviewable_type', 'academy_id']);
            $table->unique(['course_id', 'user_id']);
        });
    }
};
