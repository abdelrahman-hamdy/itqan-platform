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
        // Drop connection_quality_score from session reports tables
        Schema::table('student_session_reports', function (Blueprint $table) {
            $table->dropColumn('connection_quality_score');
        });

        Schema::table('academic_session_reports', function (Blueprint $table) {
            $table->dropColumn('connection_quality_score');
        });

        Schema::table('interactive_session_reports', function (Blueprint $table) {
            $table->dropColumn('connection_quality_score');
        });

        // Drop connection_quality_score from session attendances tables
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            $table->dropColumn('connection_quality_score');
        });

        Schema::table('academic_session_attendances', function (Blueprint $table) {
            $table->dropColumn('connection_quality_score');
        });

        Schema::table('interactive_session_attendances', function (Blueprint $table) {
            $table->dropColumn('connection_quality_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add connection_quality_score to session reports tables
        Schema::table('student_session_reports', function (Blueprint $table) {
            $table->integer('connection_quality_score')->nullable()->comment('جودة الاتصال من 1-5');
        });

        Schema::table('academic_session_reports', function (Blueprint $table) {
            $table->integer('connection_quality_score')->nullable()->comment('جودة الاتصال من 1-5');
        });

        Schema::table('interactive_session_reports', function (Blueprint $table) {
            $table->integer('connection_quality_score')->nullable();
        });

        // Re-add connection_quality_score to session attendances tables
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            $table->integer('connection_quality_score')->nullable()->comment('1-10 scale');
        });

        Schema::table('academic_session_attendances', function (Blueprint $table) {
            $table->integer('connection_quality_score')->nullable()->comment('1-10 scale');
        });

        Schema::table('interactive_session_attendances', function (Blueprint $table) {
            $table->integer('connection_quality_score')->nullable();
        });
    }
};
