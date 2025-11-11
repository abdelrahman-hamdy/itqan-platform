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
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('graduation_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->integer('graduation_year')->nullable()->after('university');
        });
    }
};