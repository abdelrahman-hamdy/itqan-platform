<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 2: Drop Duplicate Teacher Tables
     * - QuranTeacher model deleted (was using quran_teacher_profiles table)
     * - AcademicTeacher model deleted
     * - Drop academic_teachers table (0 records, unused)
     *
     * Keep: QuranTeacherProfile and AcademicTeacherProfile models
     */
    public function up(): void
    {
        // Drop academic_teachers table (unused, 0 records)
        // Note: QuranTeacher was already using quran_teacher_profiles, no table to drop
        Schema::dropIfExists('academic_teachers');
    }

    /**
     * Reverse the migrations.
     *
     * Note: Basic structure restoration only.
     * Data cannot be restored without backups.
     */
    public function down(): void
    {
        // Restore academic_teachers table structure
        Schema::create('academic_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('teacher_code')->unique();
            $table->string('education_level')->nullable();
            $table->string('university')->nullable();
            $table->integer('graduation_year')->nullable();
            $table->string('qualification_degree')->nullable();
            $table->integer('teaching_experience_years')->default(0);
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable();
            $table->json('available_days')->nullable();
            $table->time('available_time_start')->nullable();
            $table->time('available_time_end')->nullable();
            $table->decimal('session_price_individual', 8, 2)->default(0);
            $table->integer('min_session_duration')->default(45);
            $table->integer('max_session_duration')->default(60);
            $table->integer('max_students_per_group')->default(6);
            $table->text('bio_arabic')->nullable();
            $table->text('bio_english')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approval_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('status')->default('pending');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_students')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('total_courses_created')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'is_active']);
            $table->index(['is_approved', 'status']);
        });
    }
};
