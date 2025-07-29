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
        Schema::table('users', function (Blueprint $table) {
            // Add user_type field
            $table->enum('user_type', [
                'student', 
                'quran_teacher', 
                'academic_teacher', 
                'parent', 
                'supervisor', 
                'admin'
            ])->default('student')->after('role');
            
            // Add phone_verified_at for phone verification
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            
            // Add last_login_at for tracking
            $table->timestamp('last_login_at')->nullable()->after('phone_verified_at');
            
            // Ensure phone field exists and is indexed
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            $table->index('phone');
            
            // Add index for user_type for faster filtering
            $table->index(['academy_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['academy_id', 'user_type']);
            $table->dropIndex(['phone']);
            $table->dropColumn([
                'user_type',
                'phone_verified_at', 
                'last_login_at'
            ]);
        });
    }
};
