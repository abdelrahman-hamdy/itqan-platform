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
        // Only create table if it doesn't exist (might exist in schema dump)
        if (!Schema::hasTable('course_sections')) {
            Schema::create('course_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recorded_course_id')->constrained('recorded_courses')->onDelete('cascade');
                $table->string('title')->index();
                $table->string('title_en')->nullable();
                $table->text('description')->nullable();
                $table->text('description_en')->nullable();
                $table->integer('order')->default(1)->index();
                $table->boolean('is_published')->default(true)->index();
                $table->boolean('is_free_preview')->default(false);
                $table->integer('duration_minutes')->default(0);
                $table->integer('lessons_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                // Indexes for performance
                $table->index(['recorded_course_id', 'order']);
                $table->index(['recorded_course_id', 'is_published']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
