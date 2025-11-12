<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update quran_circles table to use user_id instead of teacher profile id
        // for quran_teacher_id field

        DB::statement('
            UPDATE quran_circles qc
            INNER JOIN quran_teacher_profiles qtp ON qc.quran_teacher_id = qtp.id
            SET qc.quran_teacher_id = qtp.user_id
            WHERE qc.quran_teacher_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the change - update back to teacher profile id
        DB::statement('
            UPDATE quran_circles qc
            INNER JOIN quran_teacher_profiles qtp ON qc.quran_teacher_id = qtp.user_id
            SET qc.quran_teacher_id = qtp.id
            WHERE qc.quran_teacher_id IS NOT NULL
        ');
    }
};
