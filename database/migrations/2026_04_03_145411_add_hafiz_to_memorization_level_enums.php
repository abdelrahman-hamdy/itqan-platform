<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newEnum = "'beginner','elementary','intermediate','advanced','expert','hafiz'";

        DB::statement("ALTER TABLE quran_individual_circles MODIFY COLUMN memorization_level ENUM({$newEnum}) NOT NULL DEFAULT 'beginner'");
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN memorization_level ENUM({$newEnum}) NOT NULL DEFAULT 'beginner'");
    }

    public function down(): void
    {
        $oldEnum = "'beginner','elementary','intermediate','advanced','expert'";

        DB::statement("ALTER TABLE quran_individual_circles MODIFY COLUMN memorization_level ENUM({$oldEnum}) NOT NULL DEFAULT 'beginner'");
        DB::statement("ALTER TABLE quran_circles MODIFY COLUMN memorization_level ENUM({$oldEnum}) NOT NULL DEFAULT 'beginner'");
    }
};
