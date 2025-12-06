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
        Schema::table('student_profiles', function (Blueprint $table) {
            // Add parent phone with country code support
            $table->string('parent_phone', 20)->nullable()->after('emergency_contact')
                ->comment('Parent phone number in E.164 format');
            $table->string('parent_phone_country_code', 5)->default('+966')->after('parent_phone')
                ->comment('Country calling code (e.g., +966 for Saudi Arabia)');
            $table->string('parent_phone_country', 2)->default('SA')->after('parent_phone_country_code')
                ->comment('ISO 3166-1 alpha-2 country code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['parent_phone', 'parent_phone_country_code', 'parent_phone_country']);
        });
    }
};
