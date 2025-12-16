<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration:
     * 1. Adds 'other' option to education_level enum
     * 2. Drops the obsolete qualification_degree column
     */
    public function up(): void
    {
        // Add 'other' to education_level enum
        DB::statement("ALTER TABLE academic_teacher_profiles MODIFY COLUMN education_level ENUM('diploma','bachelor','master','phd','other') NOT NULL DEFAULT 'bachelor'");

        // Drop the obsolete qualification_degree column
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('qualification_degree');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore education_level enum without 'other'
        DB::statement("ALTER TABLE academic_teacher_profiles MODIFY COLUMN education_level ENUM('diploma','bachelor','master','phd') NOT NULL DEFAULT 'bachelor'");

        // Restore qualification_degree column
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->string('qualification_degree', 255)->nullable()->after('university');
        });
    }
};
