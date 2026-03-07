<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE quran_trial_requests MODIFY COLUMN current_level ENUM('beginner','elementary','intermediate','advanced','expert','hafiz') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE quran_trial_requests MODIFY COLUMN current_level ENUM('beginner','basic','intermediate','advanced','expert') NOT NULL");
    }
};
