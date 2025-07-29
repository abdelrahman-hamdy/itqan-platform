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
        // Student Profiles
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('student_code')->unique();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('nationality', 50)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact', 20)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->enum('academic_status', ['enrolled', 'graduated', 'dropped', 'transferred'])->default('enrolled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('set null');
            $table->index(['user_id']);
        });

        // Quran Teacher Profiles
        Schema::create('quran_teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('teacher_code')->unique();
            $table->enum('educational_qualification', ['bachelor', 'master', 'phd', 'other'])->default('bachelor');
            $table->json('certifications')->nullable();
            $table->integer('teaching_experience_years')->default(0);
            $table->time('available_time_start')->default('08:00');
            $table->time('available_time_end')->default('18:00');
            $table->json('available_days')->nullable();
            $table->json('languages')->nullable();
            $table->text('bio_arabic')->nullable();
            $table->text('bio_english')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_students')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id']);
        });

        // Academic Teacher Profiles
        Schema::create('academic_teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('teacher_code')->unique();
            $table->enum('education_level', ['diploma', 'bachelor', 'master', 'phd'])->default('bachelor');
            $table->string('university')->nullable();
            $table->integer('graduation_year')->nullable();
            $table->string('qualification_degree')->nullable();
            $table->integer('teaching_experience_years')->default(0);
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable();
            $table->json('subject_ids')->nullable(); // [1, 3, 5] - Simple JSON array
            $table->json('grade_level_ids')->nullable(); // [2, 4] - Simple JSON array
            $table->json('available_days')->nullable();
            $table->time('available_time_start')->default('08:00');
            $table->time('available_time_end')->default('18:00');
            $table->decimal('session_price_individual', 8, 2)->default(100);
            $table->integer('min_session_duration')->default(45);
            $table->integer('max_session_duration')->default(90);
            $table->integer('max_students_per_group')->default(10);
            $table->text('bio_arabic')->nullable();
            $table->text('bio_english')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_students')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('total_courses_created')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id']);
        });

        // Parent Profiles
        Schema::create('parent_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('parent_code')->unique();
            $table->enum('relationship_type', ['father', 'mother', 'guardian', 'relative'])->default('father');
            $table->string('occupation')->nullable();
            $table->string('workplace')->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('passport_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('secondary_phone', 20)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->enum('preferred_contact_method', ['phone', 'email', 'sms'])->default('phone');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id']);
        });

        // Supervisor Profiles
        Schema::create('supervisor_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('supervisor_code')->unique();
            $table->enum('department', ['quran', 'academic', 'both'])->default('both');
            $table->enum('supervision_level', ['junior', 'senior', 'head'])->default('junior');
            $table->json('assigned_teachers')->nullable(); // Array of teacher IDs
            $table->json('monitoring_permissions')->nullable();
            $table->enum('reports_access_level', ['basic', 'advanced', 'full'])->default('basic');
            $table->date('hired_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->decimal('performance_rating', 3, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id']);
        });

        // Parent-Student Relationships
        Schema::create('parent_student_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('relationship_type', ['father', 'mother', 'guardian'])->default('father');
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_view_grades')->default(true);
            $table->boolean('can_receive_notifications')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('parent_profiles')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('student_profiles')->onDelete('cascade');
            $table->unique(['parent_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student_relationships');
        Schema::dropIfExists('supervisor_profiles');
        Schema::dropIfExists('parent_profiles');
        Schema::dropIfExists('academic_teacher_profiles');
        Schema::dropIfExists('quran_teacher_profiles');
        Schema::dropIfExists('student_profiles');
    }
};
