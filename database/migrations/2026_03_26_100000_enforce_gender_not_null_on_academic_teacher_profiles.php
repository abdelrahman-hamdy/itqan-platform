<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Backfill NULL gender on academic_teacher_profiles from users.gender
        DB::statement("
            UPDATE academic_teacher_profiles atp
            JOIN users u ON atp.user_id = u.id
            SET atp.gender = COALESCE(u.gender, 'male')
            WHERE atp.gender IS NULL
        ");

        // Step 2: Make gender NOT NULL on academic_teacher_profiles
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->string('gender')->nullable(false)->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->string('gender')->nullable()->default(null)->change();
        });
    }
};
