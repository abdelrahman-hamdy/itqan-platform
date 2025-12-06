<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration extends homework_submissions table with comprehensive fields
     * to support unified homework tracking across all homework types:
     * - Academic homework (written assignments, file uploads)
     * - Interactive course homework (session-tied assignments)
     * - Quran homework (view-only, tracked through StudentSessionReport)
     */
    public function up(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            // ========================================
            // Enhanced Submission Content
            // ========================================

            // Rename 'content' to 'submission_text' for clarity
            $table->renameColumn('content', 'submission_text');

            // Support multiple file uploads (JSON array of file paths)
            $table->json('submission_files')->nullable()->after('submission_text');

            // ========================================
            // Late Submission Tracking
            // ========================================

            $table->boolean('is_late')->default(false)->after('submitted_at');
            $table->integer('days_late')->default(0)->after('is_late');

            // ========================================
            // Enhanced Quality Scoring
            // ========================================

            // Keep existing 'grade' (0-10) for backward compatibility
            // Add comprehensive scoring fields
            $table->decimal('score', 5, 2)->nullable()->after('grade');
            $table->decimal('max_score', 5, 2)->default(100)->after('score');
            $table->decimal('score_percentage', 5, 2)->nullable()->after('max_score');
            $table->string('grade_letter', 2)->nullable()->after('score_percentage'); // A+, A, B+, etc.

            // ========================================
            // Enhanced Status Tracking
            // ========================================

            // Replace simple 'status' string with comprehensive enum
            // States: not_started, draft, submitted, late, graded, returned, resubmitted
            $table->enum('submission_status', [
                'not_started',  // Student hasn't started working
                'draft',        // Auto-saved draft in progress
                'submitted',    // Student submitted for grading
                'late',         // Submitted after deadline
                'graded',       // Teacher has graded
                'returned',     // Teacher returned for revision
                'resubmitted',  // Student resubmitted after revision
            ])->default('not_started')->after('status');

            // Keep old 'status' for backward compatibility temporarily
            // Will be removed in future migration after data migration

            // ========================================
            // Progress & Auto-Save Tracking
            // ========================================

            $table->decimal('progress_percentage', 5, 2)->default(0)->after('submission_status');
            $table->timestamp('last_auto_save_at')->nullable()->after('progress_percentage');
            $table->longText('auto_save_content')->nullable()->after('last_auto_save_at');

            // ========================================
            // Revision Tracking
            // ========================================

            $table->integer('revision_count')->default(0)->after('auto_save_content');
            $table->timestamp('returned_at')->nullable()->after('graded_at');
            $table->text('return_reason')->nullable()->after('returned_at');

            // ========================================
            // Homework Type Metadata
            // ========================================

            // Store homework type for quick filtering without polymorphic joins
            $table->enum('homework_type', ['academic', 'interactive', 'quran'])
                ->nullable()
                ->after('submitable_id');

            // Due date stored on submission for quick access
            $table->timestamp('due_date')->nullable()->after('homework_type');

            // ========================================
            // Additional Indexes for Performance
            // ========================================

            $table->index(['academy_id', 'submission_status']);
            $table->index(['student_id', 'homework_type']);
            $table->index(['due_date', 'submission_status']);
            $table->index(['is_late', 'submission_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['academy_id', 'submission_status']);
            $table->dropIndex(['student_id', 'homework_type']);
            $table->dropIndex(['due_date', 'submission_status']);
            $table->dropIndex(['is_late', 'submission_status']);

            // Drop new columns
            $table->dropColumn([
                'submission_files',
                'is_late',
                'days_late',
                'score',
                'max_score',
                'score_percentage',
                'grade_letter',
                'submission_status',
                'progress_percentage',
                'last_auto_save_at',
                'auto_save_content',
                'revision_count',
                'returned_at',
                'return_reason',
                'homework_type',
                'due_date',
            ]);

            // Rename back
            $table->renameColumn('submission_text', 'content');
        });
    }
};
