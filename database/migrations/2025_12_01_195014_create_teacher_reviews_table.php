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
        Schema::create('teacher_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();

            // Polymorphic relationship to teacher profiles
            $table->string('reviewable_type'); // QuranTeacherProfile or AcademicTeacherProfile
            $table->unsignedBigInteger('reviewable_id');

            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->text('comment')->nullable();

            // Approval workflow
            $table->boolean('is_approved')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // One review per student per teacher
            $table->unique(['reviewable_type', 'reviewable_id', 'student_id'], 'unique_teacher_review');
            $table->index(['reviewable_type', 'reviewable_id'], 'teacher_reviews_reviewable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_reviews');
    }
};
