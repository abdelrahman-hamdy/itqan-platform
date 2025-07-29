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
        Schema::create('quran_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->constrained()->onDelete('cascade');
            
            // Subscription Details
            $table->string('subscription_code', 50)->unique();
            $table->string('package_name', 100);
            $table->enum('package_type', ['basic', 'standard', 'premium', 'intensive', 'custom'])->default('basic');
            $table->enum('subscription_type', ['individual', 'group']);
            
            // Session Management
            $table->integer('total_sessions');
            $table->integer('sessions_used')->default(0);
            $table->integer('sessions_remaining');
            
            // Pricing
            $table->decimal('price_per_session', 8, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->enum('billing_cycle', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            
            // Payment and Status
            $table->enum('payment_status', ['paid', 'pending', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->enum('subscription_status', ['active', 'expired', 'paused', 'cancelled', 'pending', 'suspended'])->default('pending');
            
            // Trial Management
            $table->integer('trial_sessions')->default(0);
            $table->integer('trial_used')->default(0);
            $table->boolean('is_trial_active')->default(false);
            
            // Progress Tracking
            $table->integer('current_surah')->nullable();
            $table->integer('current_verse')->nullable();
            $table->integer('verses_memorized')->default(0);
            $table->enum('memorization_level', ['beginner', 'elementary', 'intermediate', 'advanced', 'expert', 'hafiz'])->default('beginner');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            
            // Session Tracking
            $table->timestamp('last_session_at')->nullable();
            
            // Subscription Period
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            
            // Pause/Resume
            $table->timestamp('paused_at')->nullable();
            $table->text('pause_reason')->nullable();
            
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Auto-renewal
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('next_payment_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            
            // Feedback and Rating
            $table->integer('rating')->nullable()->comment('1-5 rating');
            $table->text('review_text')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Administrative
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'subscription_status']);
            $table->index(['academy_id', 'payment_status']);
            $table->index(['student_id', 'subscription_status']);
            $table->index(['quran_teacher_id', 'subscription_status']);
            $table->index(['subscription_status', 'expires_at']);
            $table->index(['is_trial_active', 'trial_used']);
            $table->index(['auto_renew', 'next_payment_at']);
            $table->index('subscription_code');
            
            // Unique Constraints
            $table->unique(['academy_id', 'subscription_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_subscriptions');
    }
};
