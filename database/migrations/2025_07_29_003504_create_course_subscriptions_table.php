<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('recorded_course_id');
            
            $table->string('subscription_code')->unique();
            $table->enum('enrollment_type', ['free', 'paid', 'trial', 'gift'])->default('paid');
            $table->enum('payment_type', ['one_time', 'installment', 'subscription'])->default('one_time');
            
            // Pricing
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Access
            $table->enum('access_type', ['limited', 'lifetime'])->default('limited');
            $table->integer('access_duration_months')->default(12);
            $table->boolean('lifetime_access')->default(false);
            
            // Certificate
            $table->boolean('certificate_eligible')->default(true);
            $table->boolean('certificate_issued')->default(false);
            $table->timestamp('certificate_issued_at')->nullable();
            
            // Progress
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('completed_lessons')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->integer('watch_time_minutes')->default(0);
            $table->integer('total_duration_minutes')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('completion_date')->nullable();
            
            // Status
            $table->enum('status', ['active', 'completed', 'paused', 'expired', 'cancelled', 'refunded'])->default('active');
            
            // Dates
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->text('pause_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Refund
            $table->timestamp('refund_requested_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('refund_processed_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            
            // Student Activity
            $table->integer('notes_count')->default(0);
            $table->integer('bookmarks_count')->default(0);
            $table->integer('quiz_attempts')->default(0);
            $table->boolean('quiz_passed')->default(false);
            $table->decimal('final_score', 5, 2)->nullable();
            
            // Rating & Review
            $table->integer('rating')->nullable();
            $table->text('review_text')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Certificate
            $table->string('completion_certificate_url')->nullable();
            
            // Additional Data
            $table->json('metadata')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['academy_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['recorded_course_id', 'status']);
            $table->index(['enrollment_type', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['certificate_issued']);
            
            $table->unique(['student_id', 'recorded_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_subscriptions');
    }
};
