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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['quiz_assignment_id', 'student_id']);
            $table->index(['student_id', 'passed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
