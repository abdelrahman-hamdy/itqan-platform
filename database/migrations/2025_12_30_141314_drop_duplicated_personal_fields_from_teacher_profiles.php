<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes duplicated personal fields from teacher profile tables.
     * Personal info (first_name, last_name, email, phone) should only exist in the users table.
     */
    public function up(): void
    {
        // First, sync any data from profiles to users where user fields are empty
        $this->syncDataToUsers();

        // Drop columns from quran_teacher_profiles
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'email', 'phone', 'phone_country_code']);
        });

        // Drop columns from academic_teacher_profiles
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'email', 'phone', 'phone_country_code']);
        });
    }

    /**
     * Sync data from profile tables to users table where user fields are empty.
     */
    private function syncDataToUsers(): void
    {
        // Sync from quran_teacher_profiles
        DB::statement("
            UPDATE users u
            INNER JOIN quran_teacher_profiles p ON u.id = p.user_id
            SET
                u.first_name = COALESCE(NULLIF(u.first_name, ''), p.first_name),
                u.last_name = COALESCE(NULLIF(u.last_name, ''), p.last_name),
                u.email = COALESCE(NULLIF(u.email, ''), p.email),
                u.phone = COALESCE(NULLIF(u.phone, ''), p.phone),
                u.phone_country_code = COALESCE(NULLIF(u.phone_country_code, ''), p.phone_country_code)
            WHERE p.user_id IS NOT NULL
        ");

        // Sync from academic_teacher_profiles
        DB::statement("
            UPDATE users u
            INNER JOIN academic_teacher_profiles p ON u.id = p.user_id
            SET
                u.first_name = COALESCE(NULLIF(u.first_name, ''), p.first_name),
                u.last_name = COALESCE(NULLIF(u.last_name, ''), p.last_name),
                u.email = COALESCE(NULLIF(u.email, ''), p.email),
                u.phone = COALESCE(NULLIF(u.phone, ''), p.phone),
                u.phone_country_code = COALESCE(NULLIF(u.phone_country_code, ''), p.phone_country_code),
                u.gender = COALESCE(NULLIF(u.gender, ''), p.gender)
            WHERE p.user_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add columns to quran_teacher_profiles
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('email');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('last_name');
            $table->string('phone_country_code')->nullable()->after('phone');
        });

        // Re-add columns to academic_teacher_profiles
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('gender');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('last_name');
            $table->string('phone_country_code')->nullable()->after('phone');
        });
    }
};
