<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add supervised_individual, custom, and announcement types to the enum
        DB::statement("ALTER TABLE chat_groups MODIFY COLUMN type ENUM(
            'quran_circle',
            'individual_session',
            'academic_session',
            'interactive_course',
            'recorded_course',
            'academy_announcement',
            'supervised_individual',
            'custom',
            'announcement'
        ) NOT NULL DEFAULT 'custom'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE chat_groups MODIFY COLUMN type ENUM(
            'quran_circle',
            'individual_session',
            'academic_session',
            'interactive_course',
            'recorded_course',
            'academy_announcement'
        ) NOT NULL");
    }
};
