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
        Schema::create('quran_teachers', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Basic Info
            $table->string('teacher_code', 50)->unique();
            $table->enum('specialization', ['memorization', 'recitation', 'interpretation', 'arabic_language', 'general']);
            
            // Ijazah Information
            $table->boolean('has_ijazah')->default(false);
            $table->enum('ijazah_type', ['memorization', 'recitation', 'ten_readings', 'teaching', 'general'])->nullable();
            $table->text('ijazah_chain')->nullable();
            
            // Teaching Information
            $table->enum('memorization_level', ['beginner', 'elementary', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->integer('teaching_experience_years')->default(0);
            $table->json('available_grade_levels')->nullable();
            $table->json('teaching_methods')->nullable();
            
            // Pricing
            $table->decimal('hourly_rate_individual', 8, 2)->default(0);
            $table->decimal('hourly_rate_group', 8, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            
            // Availability
            $table->integer('max_students_per_circle')->default(8);
            $table->integer('preferred_session_duration')->default(45);
            $table->json('available_days')->nullable();
            $table->json('available_times')->nullable();
            
            // Descriptions
            $table->text('bio_ar')->nullable();
            $table->text('bio_en')->nullable();
            $table->json('certifications')->nullable();
            $table->json('achievements')->nullable();
            $table->text('teaching_philosophy')->nullable();
            
            // Status and Approval
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('inactive');
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'under_review'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Rating and Statistics
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('total_students')->default(0);
            
            // Administrative
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'status']);
            $table->index(['academy_id', 'approval_status']);
            $table->index(['specialization', 'status']);
            $table->index(['has_ijazah', 'status']);
            $table->index('rating');
            $table->index('teaching_experience_years');
            
            // Unique Constraints
            $table->unique(['academy_id', 'teacher_code']);
            $table->unique(['academy_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_teachers');
    }
};
