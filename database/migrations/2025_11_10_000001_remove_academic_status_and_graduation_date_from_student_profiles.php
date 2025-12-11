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
        if (Schema::hasColumn('student_profiles', 'academic_status')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('academic_status');
            });
        }
        if (Schema::hasColumn('student_profiles', 'graduation_date')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('graduation_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->enum('academic_status', ['enrolled', 'graduated', 'dropped', 'transferred'])
                  ->default('enrolled')
                  ->after('enrollment_date');
            $table->date('graduation_date')->nullable()->after('academic_status');
        });
    }
};
