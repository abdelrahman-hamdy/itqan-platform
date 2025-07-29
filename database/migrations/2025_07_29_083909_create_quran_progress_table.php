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
        Schema::create('quran_progress', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('circle_id')->nullable()->constrained('quran_circles')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('quran_sessions')->nullOnDelete();
            
            // Basic Information
            $table->string('progress_code', 50)->unique();
            $table->date('progress_date');
            $table->enum('progress_type', ['memorization', 'recitation', 'review', 'assessment', 'test', 'milestone'])->default('memorization');
            
            // Current Position
            $table->integer('current_surah')->nullable();
            $table->integer('current_verse')->nullable();
            $table->integer('target_surah')->nullable();
            $table->integer('target_verse')->nullable();
            
            // Session Progress
            $table->integer('verses_memorized')->default(0);
            $table->integer('verses_reviewed')->default(0);
            $table->integer('verses_perfect')->default(0);
            $table->integer('verses_need_work')->default(0);
            
            // Cumulative Progress
            $table->integer('total_verses_memorized')->default(0);
            $table->integer('total_pages_memorized')->default(0);
            $table->integer('total_surahs_completed')->default(0);
            $table->decimal('memorization_percentage', 5, 2)->default(0);
            
            // Performance Metrics
            $table->decimal('recitation_quality', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('tajweed_accuracy', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('fluency_level', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('confidence_level', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('retention_rate', 5, 1)->nullable()->comment('Percentage');
            
            // Areas for Improvement
            $table->json('common_mistakes')->nullable();
            $table->json('improvement_areas')->nullable();
            $table->json('strengths')->nullable();
            
            // Goal Management
            $table->integer('weekly_goal')->nullable();
            $table->integer('monthly_goal')->nullable();
            $table->decimal('goal_progress', 5, 2)->default(0);
            
            // Study Habits
            $table->enum('difficulty_level', ['very_easy', 'easy', 'moderate', 'challenging', 'very_challenging'])->nullable();
            $table->decimal('study_hours_this_week', 5, 2)->default(0);
            $table->decimal('average_daily_study', 4, 2)->default(0);
            
            // Review Management
            $table->date('last_review_date')->nullable();
            $table->date('next_review_date')->nullable();
            $table->integer('repetition_count')->default(0);
            
            // Achievement Tracking
            $table->enum('mastery_level', ['beginner', 'developing', 'proficient', 'advanced', 'expert', 'master'])->default('beginner');
            $table->boolean('certificate_eligible')->default(false);
            $table->json('milestones_achieved')->nullable();
            
            // Analytics and Trends
            $table->json('performance_trends')->nullable();
            $table->enum('learning_pace', ['very_slow', 'slow', 'normal', 'fast', 'very_fast'])->default('normal');
            $table->decimal('consistency_score', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('attendance_impact', 3, 1)->nullable();
            $table->decimal('homework_completion_rate', 5, 1)->nullable();
            $table->decimal('quiz_average_score', 5, 1)->nullable();
            
            // Support System
            $table->decimal('parent_involvement_level', 3, 1)->nullable()->comment('1-10 scale');
            $table->decimal('motivation_level', 3, 1)->nullable()->comment('1-10 scale');
            $table->json('challenges_faced')->nullable();
            $table->json('support_needed')->nullable();
            
            // Recommendations and Next Steps
            $table->json('recommendations')->nullable();
            $table->json('next_steps')->nullable();
            
            // Notes and Feedback
            $table->text('teacher_notes')->nullable();
            $table->text('parent_notes')->nullable();
            $table->text('student_feedback')->nullable();
            
            // Assessment
            $table->date('assessment_date')->nullable();
            $table->integer('overall_rating')->nullable()->comment('1-5 rating');
            $table->enum('progress_status', ['on_track', 'ahead', 'behind', 'needs_attention', 'excellent', 'struggling'])->default('on_track');
            
            // Administrative
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'progress_date']);
            $table->index(['student_id', 'progress_date']);
            $table->index(['quran_teacher_id', 'progress_date']);
            $table->index(['student_id', 'progress_type']);
            $table->index(['quran_subscription_id', 'progress_date']);
            $table->index(['circle_id', 'progress_date']);
            $table->index(['progress_type', 'progress_date']);
            $table->index(['progress_status', 'progress_date']);
            $table->index(['mastery_level', 'certificate_eligible']);
            $table->index(['current_surah', 'current_verse']);
            $table->index('progress_code');
            
            // Unique Constraints
            $table->unique(['academy_id', 'progress_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_progress');
    }
};
