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
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
                ->default('beginner')
                ->after('course_type')
                ->comment('Course difficulty level');

            $table->json('learning_outcomes')->nullable()->after('schedule')
                ->comment('Learning outcomes - array of outcomes');

            $table->json('prerequisites')->nullable()->after('learning_outcomes')
                ->comment('Prerequisites - array of prerequisites');

            $table->text('course_outline')->nullable()->after('prerequisites')
                ->comment('Course outline and syllabus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn(['difficulty_level', 'learning_outcomes', 'prerequisites', 'course_outline']);
        });
    }
};
