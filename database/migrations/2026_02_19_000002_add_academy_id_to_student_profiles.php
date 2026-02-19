<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_id')->nullable()->after('id');
            $table->index('academy_id');
        });

        // Backfill academy_id from grade_level relationship
        DB::statement('
            UPDATE student_profiles sp
            INNER JOIN academic_grade_levels agl ON sp.grade_level_id = agl.id
            SET sp.academy_id = agl.academy_id
            WHERE sp.grade_level_id IS NOT NULL
        ');

        // Backfill remaining null records from user relationship
        DB::statement('
            UPDATE student_profiles sp
            INNER JOIN users u ON sp.user_id = u.id
            SET sp.academy_id = u.academy_id
            WHERE sp.academy_id IS NULL AND sp.user_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex(['academy_id']);
            $table->dropColumn('academy_id');
        });
    }
};
