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
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->json('subjects_text')->nullable()->after('subject_ids')->comment('مواد التدريس كنص حر');
            $table->json('grade_levels_text')->nullable()->after('grade_level_ids')->comment('المراحل الدراسية كنص حر');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['subjects_text', 'grade_levels_text']);
        });
    }
};