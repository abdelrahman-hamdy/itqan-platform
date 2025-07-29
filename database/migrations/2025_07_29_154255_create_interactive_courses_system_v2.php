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
        // Interactive Courses (different from recorded courses)
        Schema::create('interactive_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('assigned_teacher_id'); // AcademicTeacherProfile ID
            $table->unsignedBigInteger('created_by'); // Admin user ID
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Course details
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('grade_level_id');
            $table->string('course_code')->unique();
            $table->enum('course_type', ['intensive', 'regular', 'exam_prep'])->default('regular');
            
            // Course settings
            $table->integer('max_students')->default(20);
            $table->integer('duration_weeks');
            $table->integer('sessions_per_week');
            $table->integer('session_duration_minutes')->default(60);
            $table->integer('total_sessions'); // Calculated: duration_weeks * sessions_per_week
            
            // Financial settings (set by admin)
            $table->decimal('student_price', 8, 2); // Price student pays
            $table->decimal('teacher_payment', 8, 2); // What teacher gets paid
            $table->enum('payment_type', ['fixed_amount', 'per_student', 'per_session'])->default('fixed_amount');
            
            // Schedule settings
            $table->date('start_date');
            $table->date('end_date');
            $table->date('enrollment_deadline');
            $table->json('schedule'); // Days and times
            
            // Status
            $table->enum('status', ['draft', 'published', 'active', 'completed', 'cancelled'])->default('draft');
            $table->boolean('is_published')->default(false);
            $table->timestamp('publication_date')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('assigned_teacher_id')->references('id')->on('academic_teacher_profiles')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');

            // Indexes with short names
            $table->index(['academy_id', 'status'], 'ic_academy_status_idx');
            $table->index(['assigned_teacher_id', 'status'], 'ic_teacher_status_idx');
            $table->index(['subject_id', 'grade_level_id'], 'ic_subject_grade_idx');
        });

        // Interactive Course Enrollments 
        Schema::create('interactive_course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('student_id'); // StudentProfile ID
            $table->unsignedBigInteger('enrolled_by')->nullable(); // Admin or student
            
            // Enrollment details
            $table->timestamp('enrollment_date');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->decimal('payment_amount', 8, 2);
            $table->decimal('discount_applied', 8, 2)->default(0);
            $table->enum('enrollment_status', ['enrolled', 'dropped', 'completed', 'expelled'])->default('enrolled');
            
            // Progress tracking
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->integer('attendance_count')->default(0);
            $table->integer('total_possible_attendance')->default(0);
            $table->boolean('certificate_issued')->default(false);
            
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('interactive_courses')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('student_profiles')->onDelete('cascade');
            $table->foreign('enrolled_by')->references('id')->on('users')->onDelete('set null');

            // Constraints and indexes
            $table->unique(['course_id', 'student_id']);
            $table->index(['academy_id', 'enrollment_status'], 'ice_academy_status_idx');
        });

        // Interactive Course Sessions
        Schema::create('interactive_course_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->integer('session_number');
            
            // Session details
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->integer('duration_minutes');
            $table->string('google_meet_link')->nullable();
            
            // Session status
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->integer('attendance_count')->default(0);
            $table->boolean('materials_uploaded')->default(false);
            $table->boolean('homework_assigned')->default(false);
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('course_id')->references('id')->on('interactive_courses')->onDelete('cascade');
            
            // Constraints and indexes
            $table->unique(['course_id', 'session_number']);
            $table->index(['course_id', 'scheduled_date'], 'ics_course_date_idx');
        });

        // Interactive Session Attendance
        Schema::create('interactive_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('student_id'); // StudentProfile ID
            
            // Attendance details
            $table->enum('attendance_status', ['present', 'absent', 'late'])->default('absent');
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->decimal('participation_score', 3, 1)->nullable(); // 0-10 scale
            $table->text('notes')->nullable(); // Teacher notes
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('session_id')->references('id')->on('interactive_course_sessions')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('student_profiles')->onDelete('cascade');
            
            // Constraints and indexes
            $table->unique(['session_id', 'student_id']);
            $table->index(['session_id', 'attendance_status'], 'isa_session_status_idx');
        });

        // Teacher Course Payments for Interactive Courses
        Schema::create('interactive_teacher_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('teacher_id'); // AcademicTeacherProfile ID
            
            // Payment details
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_type', ['fixed', 'per_student', 'per_session']);
            $table->integer('students_enrolled')->default(0);
            $table->decimal('amount_per_student', 8, 2)->nullable();
            $table->decimal('bonus_amount', 8, 2)->default(0);
            $table->decimal('deductions', 8, 2)->default(0);
            
            // Payment status
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable(); // Admin user ID
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('interactive_courses')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('academic_teacher_profiles')->onDelete('cascade');
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
            
            // Constraints
            $table->unique(['course_id', 'teacher_id']);
        });

        // Interactive Course Settings
        Schema::create('interactive_course_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            
            // Financial settings
            $table->enum('default_teacher_payment_type', ['fixed', 'per_student', 'per_session'])->default('fixed');
            $table->decimal('min_teacher_payment', 8, 2)->default(100);
            $table->decimal('max_discount_percentage', 5, 2)->default(20);
            
            // Academic settings
            $table->integer('min_course_duration_weeks')->default(4);
            $table->integer('max_students_per_course')->default(30);
            $table->boolean('auto_create_sessions')->default(true);
            $table->decimal('require_attendance_minimum', 5, 2)->default(75); // 75% attendance required
            
            // Technical settings
            $table->boolean('auto_create_google_meet')->default(true);
            $table->boolean('send_reminder_notifications')->default(true);
            $table->boolean('certificate_auto_generation')->default(false);
            
            // Created/updated tracking
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // One settings record per academy
            $table->unique('academy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactive_course_settings');
        Schema::dropIfExists('interactive_teacher_payments');
        Schema::dropIfExists('interactive_session_attendances');
        Schema::dropIfExists('interactive_course_sessions');
        Schema::dropIfExists('interactive_course_enrollments');
        Schema::dropIfExists('interactive_courses');
    }
};
