<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove watch_time tracking columns - feature no longer needed
     */
    public function up(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            if (Schema::hasColumn('student_progress', 'watch_time_seconds')) {
                $table->dropColumn('watch_time_seconds');
            }
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('course_subscriptions', 'watch_time_minutes')) {
                $table->dropColumn('watch_time_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('student_progress', 'watch_time_seconds')) {
                $table->integer('watch_time_seconds')->default(0)->after('progress_percentage');
            }
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('course_subscriptions', 'watch_time_minutes')) {
                $table->integer('watch_time_minutes')->default(0)->after('total_lessons');
            }
        });
    }
};
