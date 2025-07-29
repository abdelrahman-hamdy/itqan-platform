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
        Schema::create('academic_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained('academies')->onDelete('cascade');
            
            // Sessions per week options for private tutoring
            $table->json('sessions_per_week_options')->nullable();
            
            // Default session settings
            $table->integer('default_session_duration_minutes')->default(60);
            $table->decimal('default_booking_fee', 8, 2)->default(0.00);
            $table->string('currency', 3)->default('SAR');
            
            // Trial session settings
            $table->boolean('enable_trial_sessions')->default(true);
            $table->integer('trial_session_duration_minutes')->default(30);
            $table->decimal('trial_session_fee', 8, 2)->default(0.00);
            
            // Subscription settings
            $table->integer('subscription_pause_max_days')->default(30);
            $table->integer('auto_renewal_reminder_days')->default(7);
            $table->boolean('allow_mid_month_cancellation')->default(false);
            
            // Payment settings
            $table->json('enabled_payment_methods')->nullable();
            $table->decimal('late_payment_penalty_percentage', 5, 2)->default(0.00);
            
            // Google Meet integration
            $table->boolean('auto_create_google_meet_links')->default(true);
            $table->string('google_meet_account_email')->nullable();
            
            // Interactive courses settings
            $table->boolean('courses_start_on_schedule')->default(true); // No minimum students required
            $table->integer('course_enrollment_deadline_days')->default(3);
            $table->boolean('allow_late_enrollment')->default(false);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['academy_id']);
            $table->index(['academy_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_settings');
    }
};
