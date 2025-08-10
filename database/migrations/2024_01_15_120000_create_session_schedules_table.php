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
        Schema::create('session_schedules', function (Blueprint $table) {
            $table->id();
            
            // Core references
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quran_teacher_id')->constrained('quran_teacher_profiles')->cascadeOnDelete();
            $table->foreignId('quran_subscription_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('quran_circle_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Schedule identification
            $table->string('schedule_code')->unique();
            $table->string('schedule_type'); // 'subscription', 'circle', 'course'
            $table->string('title');
            $table->text('description')->nullable();
            
            // Recurrence settings
            $table->string('recurrence_pattern'); // 'weekly', 'bi-weekly', 'monthly', 'custom'
            $table->json('schedule_data'); // detailed schedule configuration
            $table->json('session_templates'); // time slots and configurations
            
            // Date range
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('max_sessions')->nullable(); // for subscription limits
            
            // Status and control
            $table->string('status')->default('active'); // active, paused, completed, cancelled
            $table->boolean('auto_generate')->default(true);
            $table->boolean('allow_rescheduling')->default(true);
            $table->integer('reschedule_hours_notice')->default(24);
            
            // Statistics
            $table->integer('sessions_generated')->default(0);
            $table->integer('sessions_completed')->default(0);
            $table->integer('sessions_cancelled')->default(0);
            $table->timestamp('last_generated_at')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['academy_id', 'schedule_type', 'status']);
            $table->index(['quran_teacher_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['auto_generate', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_schedules');
    }
};