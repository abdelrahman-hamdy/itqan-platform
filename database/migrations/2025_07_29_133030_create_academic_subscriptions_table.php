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
        Schema::create('academic_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('student_id'); // User ID of the student
            $table->unsignedBigInteger('teacher_id'); // AcademicTeacher ID
            $table->unsignedBigInteger('subject_id'); // Subject ID
            $table->unsignedBigInteger('grade_level_id'); // Grade Level ID
            $table->unsignedBigInteger('session_request_id')->nullable(); // Link to original request
            
            // Subscription details
            $table->string('subscription_code')->unique(); // SUB-{academy_id}-{sequential}
            $table->enum('subscription_type', ['private', 'group'])->default('private');
            $table->integer('sessions_per_week'); // 1-4 sessions per week
            $table->integer('session_duration_minutes')->default(60); // Duration per session
            
            // Pricing
            $table->decimal('hourly_rate', 8, 2); // Teacher's hourly rate
            $table->decimal('sessions_per_month', 5, 2); // Calculated sessions per month
            $table->decimal('monthly_amount', 8, 2); // Total monthly cost
            $table->decimal('discount_amount', 8, 2)->default(0); // Any discounts applied
            $table->decimal('final_monthly_amount', 8, 2); // Amount after discount
            $table->string('currency', 3)->default('SAR'); // Currency code
            
            // Billing and payment
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->date('start_date'); // Subscription start date
            $table->date('end_date')->nullable(); // Subscription end date (if set)
            $table->date('next_billing_date'); // Next payment due date
            $table->date('last_payment_date')->nullable(); // Last successful payment
            $table->decimal('last_payment_amount', 8, 2)->nullable(); // Last payment amount
            
            // Schedule (Finalized from SessionRequest)
            $table->json('weekly_schedule'); // {day: time, day: time, ...}
            $table->string('timezone', 50)->default('Asia/Riyadh'); // Timezone for schedule
            $table->boolean('auto_create_google_meet')->default(true); // Auto-create meeting links
            
            // Status and lifecycle
            $table->enum('status', [
                'active', // Currently active and paid
                'paused', // Temporarily paused (payment pending)
                'suspended', // Administratively suspended
                'cancelled', // Cancelled by student or admin
                'expired', // Naturally expired
                'completed' // Completed successfully
            ])->default('active');
            
            $table->enum('payment_status', [
                'current', // Up to date with payments
                'pending', // Payment due
                'overdue', // Payment overdue
                'failed', // Last payment attempt failed
                'refunded' // Payment was refunded
            ])->default('current');
            
            // Trial session tracking
            $table->boolean('has_trial_session')->default(false);
            $table->boolean('trial_session_used')->default(false);
            $table->timestamp('trial_session_date')->nullable();
            $table->enum('trial_session_status', ['scheduled', 'completed', 'missed', 'cancelled'])->nullable();
            
            // Pause/Resume functionality
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resume_date')->nullable(); // When to auto-resume
            $table->text('pause_reason')->nullable(); // Reason for pause
            $table->integer('pause_days_remaining')->default(0); // Days left in pause allowance
            
            // Auto-renewal
            $table->boolean('auto_renewal')->default(true);
            $table->integer('renewal_reminder_days')->default(7); // Days before renewal to remind
            $table->timestamp('last_reminder_sent')->nullable();
            
            // Notes and metadata
            $table->text('notes')->nullable(); // Admin notes
            $table->text('student_notes')->nullable(); // Student's notes/preferences
            $table->text('teacher_notes')->nullable(); // Teacher's notes about student
            
            // Statistics
            $table->integer('total_sessions_scheduled')->default(0);
            $table->integer('total_sessions_completed')->default(0);
            $table->integer('total_sessions_missed')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0); // Percentage
            
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['status', 'payment_status']);
            $table->index(['next_billing_date', 'status']);
            $table->index(['paused_at', 'resume_date']);
            $table->index('subscription_code');
            $table->index(['start_date', 'end_date']);

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_subscriptions');
    }
};
