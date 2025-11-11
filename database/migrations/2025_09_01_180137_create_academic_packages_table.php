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
        Schema::create('academic_packages', function (Blueprint $table) {
            $table->id();
            
            // Basic relationships
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            
            // Package information
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            
            // Package type and academic scope
            $table->enum('package_type', ['individual', 'group'])->default('individual');
            $table->json('subject_ids')->nullable(); // Array of subject IDs
            $table->json('grade_level_ids')->nullable(); // Array of grade level IDs
            
            // Session configuration
            $table->integer('sessions_per_month')->default(8);
            $table->integer('session_duration_minutes')->default(60);
            
            // Pricing
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('quarterly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2);
            $table->string('currency', 3)->default('SAR');
            
            // Features and qualifications
            $table->json('features')->nullable(); // Package features
            $table->json('teacher_qualifications')->nullable(); // Required teacher qualifications
            
            // Group package settings
            $table->integer('max_students_per_session')->default(1);
            
            // Status and ordering
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['academy_id', 'is_active']);
            $table->index(['package_type', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_packages');
    }
};
