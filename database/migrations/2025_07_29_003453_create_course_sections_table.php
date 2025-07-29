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
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recorded_course_id');
            
            // Section Information
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            
            // Section Settings
            $table->integer('order')->default(1);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_free_preview')->default(false);
            
            // Stats (auto-calculated)
            $table->integer('duration_minutes')->default(0);
            $table->integer('lessons_count')->default(0);
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['recorded_course_id', 'order']);
            $table->index(['recorded_course_id', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
