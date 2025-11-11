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
        // Add missing columns to academy_settings table
        Schema::table('academy_settings', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('academy_settings', 'default_late_tolerance_minutes')) {
                $table->tinyInteger('default_late_tolerance_minutes')->unsigned()->default(10)->after('default_buffer_minutes');
            }
            
            if (!Schema::hasColumn('academy_settings', 'requires_session_approval')) {
                $table->boolean('requires_session_approval')->default(false)->after('default_late_tolerance_minutes');
            }
            
            if (!Schema::hasColumn('academy_settings', 'allows_teacher_creation')) {
                $table->boolean('allows_teacher_creation')->default(true)->after('requires_session_approval');
            }
            
            if (!Schema::hasColumn('academy_settings', 'allows_student_enrollment')) {
                $table->boolean('allows_student_enrollment')->default(true)->after('allows_teacher_creation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_late_tolerance_minutes',
                'requires_session_approval',
                'allows_teacher_creation', 
                'allows_student_enrollment'
            ]);
        });
    }
};
