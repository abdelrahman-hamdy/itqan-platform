<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $profileTables = [
            'student_profiles',
            'quran_teacher_profiles',
            'academic_teacher_profiles',
            'parent_profiles',
            'supervisor_profiles',
        ];

        foreach ($profileTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('avatar')->nullable()->after('phone');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $profileTables = [
            'student_profiles',
            'quran_teacher_profiles',
            'academic_teacher_profiles',
            'parent_profiles',
            'supervisor_profiles',
        ];

        foreach ($profileTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('avatar');
                });
            }
        }
    }
};
