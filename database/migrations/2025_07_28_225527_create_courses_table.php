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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            $table->unsignedBigInteger('teacher_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['individual', 'group', 'recorded'])->default('group');
            $table->string('level')->default('beginner'); // beginner, intermediate, advanced
            $table->integer('duration_weeks')->default(8);
            $table->integer('sessions_per_week')->default(2);
            $table->integer('session_duration_minutes')->default(60);
            $table->integer('max_students')->default(10);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->boolean('is_active')->default(true);
            $table->datetime('starts_at')->nullable();
            $table->datetime('ends_at')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'is_active']);
            $table->index(['teacher_id', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
