<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['quran_sessions', 'academic_sessions', 'interactive_course_sessions'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->json('telemetry_summary')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['quran_sessions', 'academic_sessions', 'interactive_course_sessions'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('telemetry_summary');
            });
        }
    }
};
