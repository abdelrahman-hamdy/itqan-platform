<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE quran_teacher_profiles MODIFY COLUMN educational_qualification ENUM('diploma','bachelor','master','phd','other') NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE quran_teacher_profiles MODIFY COLUMN educational_qualification ENUM('bachelor','master','phd','other') NOT NULL DEFAULT 'other'");
    }
};
