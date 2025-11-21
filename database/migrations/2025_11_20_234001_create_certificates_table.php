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
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('academy_id')->constrained('academies')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();

            // Polymorphic relationship to subscription/enrollment
            $table->uuidMorphs('certificateable');

            // Certificate details
            $table->string('certificate_number')->unique();
            $table->enum('certificate_type', ['recorded_course', 'interactive_course', 'quran_subscription', 'academic_subscription']);
            $table->enum('template_style', ['modern', 'classic', 'elegant'])->default('modern');
            $table->text('certificate_text');

            // Issuance details
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path');
            $table->boolean('is_manual')->default(false);
            $table->text('custom_achievement_text')->nullable();

            // Additional metadata (completion %, grade, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes (uuidMorphs already creates index for certificateable)
            $table->index('certificate_number');
            $table->index(['academy_id', 'student_id']);
            $table->index('certificate_type');
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
