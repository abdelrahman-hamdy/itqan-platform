<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('quran_teacher_id')->nullable();
            $table->unsignedBigInteger('quran_circle_id')->nullable();
            
            $table->string('subscription_code')->unique();
            $table->enum('subscription_type', ['individual', 'circle'])->default('individual');
            $table->enum('session_type', ['online', 'offline', 'hybrid'])->default('online');
            $table->enum('specialization', ['memorization', 'recitation', 'tajweed', 'complete'])->default('memorization');
            $table->string('recitation_style')->nullable();
            $table->enum('memorization_level', ['beginner', 'intermediate', 'advanced', 'hafiz'])->default('beginner');
            
            // Schedule
            $table->integer('sessions_per_week')->default(3);
            $table->integer('session_duration_minutes')->default(30);
            $table->json('preferred_schedule')->nullable();
            
            // Pricing
            $table->decimal('price_per_session', 8, 2)->default(0);
            $table->decimal('monthly_fee', 8, 2)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->enum('payment_method', ['monthly', 'per_session', 'package'])->default('monthly');
            
            // Status
            $table->enum('status', ['trial', 'active', 'paused', 'expired', 'cancelled'])->default('trial');
            
            // Trial & Sessions
            $table->integer('trial_sessions')->default(2);
            $table->integer('trial_used')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('completed_sessions')->default(0);
            $table->integer('missed_sessions')->default(0);
            $table->integer('makeup_sessions_allowed')->default(3);
            $table->integer('makeup_sessions_used')->default(0);
            
            // Progress
            $table->text('progress_notes')->nullable();
            $table->string('current_surah')->nullable();
            $table->integer('current_verse')->nullable();
            $table->integer('memorized_verses_count')->default(0);
            $table->integer('memorized_pages_count')->default(0);
            $table->json('memorized_surahs')->nullable();
            $table->enum('tajweed_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->decimal('recitation_accuracy', 3, 1)->default(0);
            $table->decimal('performance_rating', 3, 1)->default(0);
            
            // Goals & Requirements
            $table->json('goals')->nullable();
            $table->json('special_requirements')->nullable();
            
            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_session_at')->nullable();
            $table->timestamp('next_session_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->text('pause_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Approval
            $table->boolean('parent_approval')->default(false);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['academy_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['quran_teacher_id', 'status']);
            $table->index(['subscription_type', 'status']);
            $table->index(['specialization', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_subscriptions');
    }
};
