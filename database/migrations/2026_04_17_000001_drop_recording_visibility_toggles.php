<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['quran_circles', 'quran_individual_circles', 'interactive_courses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['show_recording_to_teacher', 'show_recording_to_student']);
            });
        }
    }

    public function down(): void
    {
        foreach (['quran_circles', 'quran_individual_circles', 'interactive_courses'] as $tableName) {
            // Note: original per-row visibility values cannot be restored — rollback resets to defaults.
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('show_recording_to_teacher')->default(true)->after('recording_enabled');
                $table->boolean('show_recording_to_student')->default(false)->after('show_recording_to_teacher');
            });
        }
    }
};
