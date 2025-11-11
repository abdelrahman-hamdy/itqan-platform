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
        // Skip creating academic_teacher_subjects table as it already exists

        // Skip creating academic_teacher_grade_levels table as it already exists

        // Migrate existing data from JSON arrays to pivot tables
        $teachers = DB::table('academic_teacher_profiles')
            ->whereNotNull('subject_ids')
            ->where('subject_ids', '!=', '[]')
            ->get();

        foreach ($teachers as $teacher) {
            $subjectIds = json_decode($teacher->subject_ids, true);
            foreach ($subjectIds as $subjectId) {
                DB::table('academic_teacher_subjects')->insertOrIgnore([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subjectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $teachers = DB::table('academic_teacher_profiles')
            ->whereNotNull('grade_level_ids')
            ->where('grade_level_ids', '!=', '[]')
            ->get();

        foreach ($teachers as $teacher) {
            $gradeLevelIds = json_decode($teacher->grade_level_ids, true);
            foreach ($gradeLevelIds as $gradeLevelId) {
                DB::table('academic_teacher_grade_levels')->insertOrIgnore([
                    'teacher_id' => $teacher->id,
                    'grade_level_id' => $gradeLevelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_teacher_subjects');
        Schema::dropIfExists('academic_teacher_grade_levels');
    }
};
