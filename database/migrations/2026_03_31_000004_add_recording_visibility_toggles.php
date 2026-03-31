<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['quran_group_circles', 'quran_individual_circles', 'interactive_courses'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('show_recording_to_teacher')->default(true)->after('recording_enabled');
                $table->boolean('show_recording_to_student')->default(false)->after('show_recording_to_teacher');
            });
        }
    }

    public function down(): void
    {
        $tables = ['quran_group_circles', 'quran_individual_circles', 'interactive_courses'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['show_recording_to_teacher', 'show_recording_to_student']);
            });
        }
    }
};
