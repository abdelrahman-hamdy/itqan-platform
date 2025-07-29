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
        Schema::create('academic_teachers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('academy_id');
            $table->string('teacher_code')->unique();
            $table->enum('specialization_field', ['mathematics', 'physics', 'chemistry', 'biology', 'arabic_language', 'english_language', 'history', 'geography', 'islamic_studies', 'computer_science', 'art', 'music', 'physical_education', 'philosophy', 'psychology', 'sociology', 'economics'])->default('mathematics');
            $table->enum('education_level', ['diploma', 'bachelor', 'master', 'phd'])->default('bachelor');
            $table->string('university')->nullable();
            $table->integer('graduation_year')->nullable();
            $table->string('qualification_degree')->nullable();
            $table->text('qualification_details')->nullable();
            $table->integer('teaching_experience_years')->default(0);
            $table->json('certifications')->nullable(); // [{'name': '', 'issuer': '', 'year': '', 'expiry': ''}]
            $table->json('languages')->nullable(); // ['ar', 'en', 'fr']
            $table->json('preferred_teaching_methods')->nullable(); // ['lecture', 'interactive', 'problem_solving', etc.]
            $table->json('available_days')->nullable();
            $table->json('available_times')->nullable();
            $table->decimal('session_price_individual', 8, 2)->default(0);
            $table->decimal('session_price_group', 8, 2)->default(0);
            $table->integer('min_session_duration')->default(45); // minutes
            $table->integer('max_session_duration')->default(90); // minutes
            $table->integer('max_students_per_group')->default(6);
            $table->text('bio_arabic')->nullable();
            $table->text('bio_english')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('cv_file_path')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approval_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended', 'rejected'])->default('pending');
            $table->decimal('rating', 3, 2)->default(0); // out of 5
            $table->integer('total_students')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('total_courses_created')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('can_create_courses')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'is_active']);
            $table->index(['specialization_field', 'is_approved']);
            $table->index(['status', 'is_active']);
            $table->index(['education_level', 'is_active']);
            $table->index('teacher_code');

            // Foreign keys will be added later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_teachers');
    }
};
