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
        Schema::create('quran_circles', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('quran_teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Circle Details
            $table->string('circle_code', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            
            // Circle Type and Specifications
            $table->enum('circle_type', ['memorization', 'recitation', 'mixed', 'advanced', 'beginners'])->default('memorization');
            $table->enum('specialization', ['memorization', 'recitation', 'interpretation', 'arabic_language', 'complete'])->default('memorization');
            $table->enum('memorization_level', ['beginner', 'elementary', 'intermediate', 'advanced', 'expert'])->default('beginner');
            
            // Age and Grade Restrictions
            $table->json('grade_levels')->nullable();
            $table->integer('age_range_min')->nullable();
            $table->integer('age_range_max')->nullable();
            
            // Capacity Management
            $table->integer('max_students')->default(8);
            $table->integer('current_students')->default(0);
            $table->integer('min_students_to_start')->default(3);
            
            // Scheduling
            $table->integer('session_duration_minutes')->default(60);
            $table->json('weekly_schedule')->nullable();
            $table->json('schedule_days')->nullable();
            $table->json('schedule_times')->nullable();
            $table->string('timezone', 50)->default('Asia/Riyadh');
            
            // Pricing
            $table->decimal('price_per_student', 8, 2)->default(0);
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->decimal('enrollment_fee', 8, 2)->default(0);
            $table->decimal('materials_fee', 8, 2)->default(0);
            
            // Progress Management
            $table->integer('total_sessions_planned')->default(0);
            $table->integer('sessions_completed')->default(0);
            $table->integer('current_surah')->nullable();
            $table->integer('current_verse')->nullable();
            
            // Teaching Configuration
            $table->text('teaching_method')->nullable();
            $table->json('materials_used')->nullable();
            $table->json('requirements')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('assessment_methods')->nullable();
            
            // Status Management
            $table->enum('status', ['planning', 'pending', 'active', 'ongoing', 'completed', 'cancelled', 'suspended'])->default('planning');
            $table->enum('enrollment_status', ['open', 'closed', 'full', 'waitlist'])->default('closed');
            
            // Important Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('registration_deadline')->nullable();
            $table->timestamp('last_session_at')->nullable();
            $table->timestamp('next_session_at')->nullable();
            
            // Online Meeting Configuration
            $table->string('room_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->boolean('recording_enabled')->default(false);
            
            // Circle Settings
            $table->boolean('attendance_required')->default(true);
            $table->boolean('makeup_sessions_allowed')->default(true);
            $table->boolean('certificates_enabled')->default(true);
            
            // Statistics and Rating
            $table->decimal('avg_rating', 2, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('dropout_rate', 5, 2)->default(0);
            
            // Administrative
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['academy_id', 'enrollment_status']);
            $table->index(['quran_teacher_id', 'status']);
            $table->index(['circle_type', 'specialization']);
            $table->index(['status', 'start_date']);
            $table->index(['enrollment_status', 'registration_deadline']);
            $table->index(['memorization_level', 'status']);
            $table->index('avg_rating');
            $table->index('circle_code');
            
            // Unique Constraints
            $table->unique(['academy_id', 'circle_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_circles');
    }
};
