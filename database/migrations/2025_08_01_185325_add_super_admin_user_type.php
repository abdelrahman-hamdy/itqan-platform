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
        // First, update the enum to include 'super_admin'
        DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('student', 'quran_teacher', 'academic_teacher', 'parent', 'supervisor', 'admin', 'super_admin') DEFAULT 'student'");
        
        // Update existing super admins (admins with academy_id = null) to have user_type = 'super_admin'
        DB::table('users')
            ->where('user_type', 'admin')
            ->whereNull('academy_id')
            ->update(['user_type' => 'super_admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert super admins back to 'admin' type
        DB::table('users')
            ->where('user_type', 'super_admin')
            ->update(['user_type' => 'admin']);
            
        // Remove 'super_admin' from enum
        DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('student', 'quran_teacher', 'academic_teacher', 'parent', 'supervisor', 'admin') DEFAULT 'student'");
    }
};