<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->unsignedInteger('teacher_reschedule_deadline_hours')
                ->default(24)
                ->after('default_buffer_minutes')
                ->comment('Minimum hours before session start for teacher reschedule. 0 = no restriction.');
        });
    }

    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->dropColumn('teacher_reschedule_deadline_hours');
        });
    }
};
