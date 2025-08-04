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
        Schema::create('quran_trial_requests', function (Blueprint $table) {
            $table->id();
            
            // Core references
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('quran_teacher_profiles')->cascadeOnDelete();
            
            // Request identification
            $table->string('request_code')->unique();
            
            // Student information
            $table->string('student_name');
            $table->integer('student_age')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            
            // Learning information
            $table->enum('current_level', ['beginner', 'basic', 'intermediate', 'advanced', 'expert']);
            $table->json('learning_goals')->nullable(); // array of goals
            $table->enum('preferred_time', ['morning', 'afternoon', 'evening'])->nullable();
            $table->text('notes')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending', 
                'approved', 
                'rejected', 
                'scheduled', 
                'completed', 
                'cancelled', 
                'no_show'
            ])->default('pending');
            
            // Teacher response
            $table->text('teacher_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            
            // Session scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('meeting_password')->nullable();
            
            // Session completion
            $table->foreignId('trial_session_id')->nullable()->constrained('quran_sessions')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->integer('rating')->nullable(); // 1-5 rating
            $table->text('feedback')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['academy_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index('scheduled_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_trial_requests');
    }
};