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
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'lesson_code',
                'lesson_type',
                'video_duration_seconds',
                'estimated_study_time_minutes',
                'difficulty_level',
                'notes',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('lesson_code', 50)->nullable();
            $table->string('lesson_type', 50)->default('video');
            $table->integer('video_duration_seconds')->nullable();
            $table->integer('estimated_study_time_minutes')->nullable();
            $table->string('difficulty_level', 20)->default('medium');
            $table->text('notes')->nullable();
        });
    }
};
