<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds recording_enabled to interactive_courses table to control
     * whether all sessions in the course should be recorded.
     * This setting was previously on individual sessions but moved to course level
     * for better management.
     */
    public function up(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->boolean('recording_enabled')->default(true)->after('certificate_template_style')
                ->comment('Enable/disable recording for all course sessions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn('recording_enabled');
        });
    }
};
