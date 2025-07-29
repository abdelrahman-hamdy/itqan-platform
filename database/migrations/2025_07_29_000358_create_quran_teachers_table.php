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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('academy_id');
            $table->string('teacher_code')->unique();
            $table->enum('specialization', ['memorization', 'tajweed', 'recitation', 'interpretation', 'arabic_language', 'islamic_studies'])->default('memorization');
            $table->boolean('has_ijazah')->default(false);
            $table->string('ijazah_type')->nullable();
            $table->string('ijazah_from')->nullable();
            $table->date('ijazah_date')->nullable();
            $table->enum('memorization_level', ['hafez', 'partial_hafez', 'beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->integer('teaching_experience_years')->default(0);
            $table->json('preferred_age_groups')->nullable(); // ['children', 'teenagers', 'adults']
            $table->json('teaching_methods')->nullable(); // ['traditional', 'modern', 'mixed', 'interactive', 'gamification']
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', etc.]
            $table->json('available_times')->nullable(); // time slots
            $table->decimal('session_price_individual', 8, 2)->default(0);
            $table->decimal('session_price_group', 8, 2)->default(0);
            $table->integer('min_session_duration')->default(30); // minutes
            $table->integer('max_session_duration')->default(60); // minutes
            $table->integer('max_students_per_circle')->default(8);
            $table->text('bio_arabic')->nullable();
            $table->text('bio_english')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approval_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended', 'rejected'])->default('pending');
            $table->decimal('rating', 3, 2)->default(0); // out of 5
            $table->integer('total_students')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['academy_id', 'is_active']);
            $table->index(['specialization', 'is_approved']);
            $table->index(['status', 'is_active']);
            $table->index('teacher_code');

            // Foreign keys will be added later to avoid circular dependencies
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
