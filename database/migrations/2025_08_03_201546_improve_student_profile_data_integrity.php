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
        // First, repair any existing orphaned student profiles
        DB::statement('
            UPDATE student_profiles 
            SET grade_level_id = (
                SELECT gl.id 
                FROM academic_grade_levels gl 
                INNER JOIN users u ON u.academy_id = gl.academy_id 
                WHERE u.id = student_profiles.user_id 
                AND gl.is_active = 1 
                ORDER BY gl.name ASC 
                LIMIT 1
            ) 
            WHERE grade_level_id IS NULL 
            AND user_id IS NOT NULL
        ');
        
        // Add an index to improve performance for academy-scoped queries
        Schema::table('student_profiles', function (Blueprint $table) {
            // Add composite index for user_id and grade_level_id
            $table->index(['user_id', 'grade_level_id'], 'idx_student_profiles_user_grade');
            
            // Add index for grade_level_id for faster joins
            $table->index(['grade_level_id'], 'idx_student_profiles_grade_level');
        });
        
        // Create a trigger to prevent deletion of grade levels with associated students
        DB::unprepared('
            CREATE TRIGGER prevent_grade_level_deletion 
            BEFORE DELETE ON academic_grade_levels 
            FOR EACH ROW 
            BEGIN 
                IF (SELECT COUNT(*) FROM student_profiles WHERE grade_level_id = OLD.id) > 0 THEN 
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Cannot delete grade level: students are still assigned to it. Please reassign students first."; 
                END IF; 
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the trigger
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_grade_level_deletion');
        
        // Drop the indexes
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_student_profiles_user_grade');
            $table->dropIndex('idx_student_profiles_grade_level');
        });
    }
};
