<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE quran_individual_circles MODIFY COLUMN specialization ENUM('memorization','recitation','interpretation','arabic_language','complete','tajweed') NOT NULL DEFAULT 'memorization'");
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN specialization ENUM('memorization','recitation','interpretation','arabic_language','complete','tajweed') NOT NULL DEFAULT 'memorization'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE quran_individual_circles MODIFY COLUMN specialization ENUM('memorization','recitation','interpretation','arabic_language','complete') NOT NULL DEFAULT 'memorization'");
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN specialization ENUM('memorization','recitation','interpretation','arabic_language','complete') NOT NULL DEFAULT 'memorization'");
    }
};
