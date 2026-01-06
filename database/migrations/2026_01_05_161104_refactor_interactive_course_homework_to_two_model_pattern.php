<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor Interactive Course Homework to Two-Model Pattern
 *
 * This migration aligns Interactive Course homework with Academic homework structure:
 * - InteractiveCourseHomework (assignment) → InteractiveCourseHomeworkSubmission (per-student)
 *
 * Steps:
 * 1. Create new interactive_course_homework table (assignments)
 * 2. Rename existing interactive_course_homework → interactive_course_homework_submissions
 * 3. Add foreign key to link submissions to assignments
 * 4. Migrate data - create assignments from existing submissions/session data
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Check if the old table exists
        if (! Schema::hasTable('interactive_course_homework')) {
            // Table doesn't exist, create both tables from scratch
            $this->createFreshTables();

            return;
        }

        // Step 2: Create the new assignment table FIRST (with temporary name)
        Schema::create('interactive_course_homework_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('interactive_course_session_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->json('teacher_files')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->boolean('allow_late_submissions')->default(true);
            $table->decimal('max_score', 5, 2)->default(10);
            $table->string('status')->default('published');
            $table->boolean('is_active')->default(true);
            $table->integer('total_students')->default(0);
            $table->integer('submitted_count')->default(0);
            $table->integer('graded_count')->default(0);
            $table->integer('late_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Use shorter constraint names (MySQL 64 char limit)
            $table->foreign('academy_id', 'ic_hw_assign_academy_fk')->references('id')->on('academies')->cascadeOnDelete();
            $table->foreign('interactive_course_session_id', 'ic_hw_assign_session_fk')->references('id')->on('interactive_course_sessions')->cascadeOnDelete();
            $table->foreign('teacher_id', 'ic_hw_assign_teacher_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'ic_hw_assign_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'ic_hw_assign_updated_fk')->references('id')->on('users')->nullOnDelete();

            $table->index(['academy_id', 'status'], 'ic_hw_assign_academy_status_idx');
            $table->index(['interactive_course_session_id'], 'ic_hw_assign_session_idx');
        });

        // Step 3: Rename existing table to submissions
        Schema::rename('interactive_course_homework', 'interactive_course_homework_submissions');

        // Step 4: Add homework_id column to submissions (nullable initially)
        Schema::table('interactive_course_homework_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('interactive_course_homework_id')->nullable()->after('academy_id');
        });

        // Step 5: Migrate data - Create assignment records from session data
        $this->migrateExistingData();

        // Step 6: Add foreign key constraint after data migration
        Schema::table('interactive_course_homework_submissions', function (Blueprint $table) {
            $table->foreign('interactive_course_homework_id', 'ic_hw_sub_homework_fk')
                ->references('id')
                ->on('interactive_course_homework_assignments')
                ->cascadeOnDelete();
        });

        // Step 7: Rename assignment table to final name
        Schema::rename('interactive_course_homework_assignments', 'interactive_course_homework');
    }

    public function down(): void
    {
        // Reverse the migration
        if (Schema::hasTable('interactive_course_homework')) {
            Schema::rename('interactive_course_homework', 'interactive_course_homework_assignments');
        }

        if (Schema::hasTable('interactive_course_homework_submissions')) {
            // Check if the column exists before trying to drop
            $hasColumn = Schema::hasColumn('interactive_course_homework_submissions', 'interactive_course_homework_id');

            if ($hasColumn) {
                // Remove foreign key and column
                Schema::table('interactive_course_homework_submissions', function (Blueprint $table) {
                    // Try to drop the foreign key - use try/catch for safety
                    try {
                        $table->dropForeign(['interactive_course_homework_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist or have a different name
                    }
                    $table->dropColumn('interactive_course_homework_id');
                });
            }

            // Rename back
            Schema::rename('interactive_course_homework_submissions', 'interactive_course_homework');
        }

        // Drop assignments table
        Schema::dropIfExists('interactive_course_homework_assignments');
    }

    /**
     * Create fresh tables when starting from scratch
     */
    private function createFreshTables(): void
    {
        // Create assignments table
        Schema::create('interactive_course_homework', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('interactive_course_session_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->json('teacher_files')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->boolean('allow_late_submissions')->default(true);
            $table->decimal('max_score', 5, 2)->default(10);
            $table->string('status')->default('published');
            $table->boolean('is_active')->default(true);
            $table->integer('total_students')->default(0);
            $table->integer('submitted_count')->default(0);
            $table->integer('graded_count')->default(0);
            $table->integer('late_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Use shorter constraint names (MySQL 64 char limit)
            $table->foreign('academy_id', 'ic_hw_academy_fk')->references('id')->on('academies')->cascadeOnDelete();
            $table->foreign('interactive_course_session_id', 'ic_hw_session_fk')->references('id')->on('interactive_course_sessions')->cascadeOnDelete();
            $table->foreign('teacher_id', 'ic_hw_teacher_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'ic_hw_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'ic_hw_updated_fk')->references('id')->on('users')->nullOnDelete();

            $table->index(['academy_id', 'status'], 'ic_hw_academy_status_idx');
            $table->index(['interactive_course_session_id'], 'ic_hw_session_idx');
        });

        // Create submissions table
        Schema::create('interactive_course_homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('interactive_course_homework_id');
            $table->unsignedBigInteger('interactive_course_session_id');
            $table->unsignedBigInteger('student_id');
            $table->text('submission_text')->nullable();
            $table->json('submission_files')->nullable();
            $table->string('submission_status')->default('pending');
            $table->dateTime('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->integer('days_late')->default(0);
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->default(10);
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Use shorter constraint names (MySQL 64 char limit)
            $table->foreign('academy_id', 'ic_hw_sub_academy_fk')->references('id')->on('academies')->cascadeOnDelete();
            $table->foreign('interactive_course_homework_id', 'ic_hw_sub_hw_fk')->references('id')->on('interactive_course_homework')->cascadeOnDelete();
            $table->foreign('interactive_course_session_id', 'ic_hw_sub_session_fk')->references('id')->on('interactive_course_sessions')->cascadeOnDelete();
            $table->foreign('student_id', 'ic_hw_sub_student_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('graded_by', 'ic_hw_sub_grader_fk')->references('id')->on('users')->nullOnDelete();

            $table->index(['academy_id', 'submission_status'], 'ic_hw_sub_academy_status_idx');
            $table->index(['student_id'], 'ic_hw_sub_student_idx');
            $table->index(['interactive_course_homework_id'], 'ic_hw_sub_homework_idx');
            $table->unique(['interactive_course_homework_id', 'student_id'], 'ic_hw_sub_unique');
        });
    }

    /**
     * Migrate existing data - create assignment records for submissions
     */
    private function migrateExistingData(): void
    {
        // Get all unique session_ids that have submissions
        $sessionIds = DB::table('interactive_course_homework_submissions')
            ->distinct()
            ->pluck('session_id');

        foreach ($sessionIds as $sessionId) {
            // Get session data
            $session = DB::table('interactive_course_sessions')
                ->where('id', $sessionId)
                ->first();

            if (! $session) {
                continue;
            }

            // Get academy_id from course
            $course = DB::table('interactive_courses')
                ->where('id', $session->course_id)
                ->first();

            $academyId = $course?->academy_id;
            if (! $academyId) {
                continue;
            }

            // Create assignment record
            $homeworkId = DB::table('interactive_course_homework_assignments')->insertGetId([
                'academy_id' => $academyId,
                'interactive_course_session_id' => $sessionId,
                'teacher_id' => $course->assigned_teacher_id ?? null,
                'title' => $session->homework_description
                    ? mb_substr($session->homework_description, 0, 100)
                    : 'واجب الجلسة '.($session->session_number ?? $sessionId),
                'description' => $session->homework_description ?? null,
                'teacher_files' => $session->homework_file ? json_encode([$session->homework_file]) : null,
                'due_date' => null, // Was not stored before
                'allow_late_submissions' => true,
                'max_score' => 10,
                'status' => 'published',
                'is_active' => true,
                'created_at' => $session->created_at ?? now(),
                'updated_at' => now(),
            ]);

            // Update submissions to link to the assignment
            DB::table('interactive_course_homework_submissions')
                ->where('session_id', $sessionId)
                ->update(['interactive_course_homework_id' => $homeworkId]);
        }
    }
};
