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
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Student ID
            $table->unsignedBigInteger('course_id');
            $table->datetime('enrolled_at');
            $table->datetime('completed_at')->nullable();
            $table->enum('status', ['enrolled', 'active', 'completed', 'dropped', 'suspended'])->default('enrolled');
            $table->decimal('progress_percentage', 5, 2)->default(0); // 0.00 to 100.00
            $table->decimal('final_grade', 5, 2)->nullable(); // Final grade if completed
            $table->text('notes')->nullable(); // Admin or teacher notes
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['enrolled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
