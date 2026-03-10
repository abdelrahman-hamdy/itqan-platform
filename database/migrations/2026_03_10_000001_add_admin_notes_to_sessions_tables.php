<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('supervisor_notes');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('supervisor_notes');
        });

        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('supervisor_notes');
        });
    }

    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });

        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });
    }
};
