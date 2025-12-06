<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds phone_country_code to all user-related tables to standardize phone storage:
     * - Phone number stored WITHOUT country code
     * - Country code stored separately in phone_country_code field
     */
    public function up(): void
    {
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_country_code', 5)->default('+966')->after('phone')
                ->comment('Country calling code (e.g., +966 for Saudi Arabia)');
        });

        // Parent profiles table
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->string('phone_country_code', 5)->default('+966')->after('phone')
                ->comment('Country calling code for primary phone');
            $table->string('secondary_phone_country_code', 5)->nullable()->after('secondary_phone')
                ->comment('Country calling code for secondary phone');
        });

        // Student profiles table - add for main phone (parent_phone already has it)
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('phone_country_code', 5)->default('+966')->after('phone')
                ->comment('Country calling code for student phone');
            $table->string('emergency_contact_country_code', 5)->nullable()->after('emergency_contact')
                ->comment('Country calling code for emergency contact');
        });

        // Quran teacher profiles table
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->string('phone_country_code', 5)->default('+966')->after('phone')
                ->comment('Country calling code');
        });

        // Academic teacher profiles table
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->string('phone_country_code', 5)->default('+966')->after('phone')
                ->comment('Country calling code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });

        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn(['phone_country_code', 'secondary_phone_country_code']);
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['phone_country_code', 'emergency_contact_country_code']);
        });

        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });

        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });
    }
};
