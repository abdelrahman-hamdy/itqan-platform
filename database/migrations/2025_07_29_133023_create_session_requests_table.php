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
        Schema::create('session_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('student_id'); // User ID of the student
            $table->unsignedBigInteger('teacher_id'); // AcademicTeacher ID
            $table->unsignedBigInteger('subject_id'); // Subject ID
            $table->unsignedBigInteger('grade_level_id'); // Grade Level ID
            
            // Request details
            $table->string('request_code')->unique(); // REQ-{academy_id}-{sequential}
            $table->integer('sessions_per_week'); // 1-4 sessions per week
            $table->decimal('hourly_rate', 8, 2); // Teacher's hourly rate at time of request
            $table->decimal('total_monthly_cost', 8, 2)->nullable(); // Calculated monthly cost
            $table->boolean('is_trial_request')->default(false); // Is this a trial session request
            
            // Status and workflow
            $table->enum('status', [
                'pending', // Initial request, waiting for teacher response
                'teacher_proposed', // Teacher has proposed times
                'student_negotiating', // Student requested changes
                'teacher_revising', // Teacher is revising the proposal
                'agreed', // Both parties agreed, ready for payment
                'paid', // Payment completed, subscription created
                'rejected', // Teacher rejected the request
                'cancelled', // Student cancelled the request
                'expired' // Request expired due to inactivity
            ])->default('pending');
            
            // Proposed schedule (JSON format)
            $table->json('proposed_schedule')->nullable(); // {day: time, day: time, ...}
            $table->json('current_proposal')->nullable(); // Latest schedule proposal
            
            // Communication
            $table->text('initial_message')->nullable(); // Student's initial request message
            $table->text('teacher_response')->nullable(); // Teacher's response with proposed times
            $table->text('latest_message')->nullable(); // Latest message in the conversation
            $table->timestamp('last_activity_at')->nullable(); // For tracking inactivity
            
            // Trial session details
            $table->boolean('trial_session_completed')->default(false);
            $table->timestamp('trial_session_date')->nullable();
            $table->text('trial_session_feedback')->nullable();
            
            // Metadata
            $table->timestamp('teacher_responded_at')->nullable();
            $table->timestamp('agreed_at')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-expire after X days
            $table->unsignedBigInteger('created_subscription_id')->nullable(); // Link to created subscription
            
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('request_code');
            $table->index('last_activity_at');

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_requests');
    }
};
