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
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Teacher ID
            $table->unsignedBigInteger('subject_id');
            $table->string('proficiency_level')->default('intermediate'); // beginner, intermediate, advanced, expert
            $table->boolean('is_certified')->default(false);
            $table->text('notes')->nullable(); // Additional qualifications or notes
            $table->timestamps();

            $table->unique(['user_id', 'subject_id']);
            $table->index(['user_id']);
            $table->index(['subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
