<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->boolean('is_fully_booked')->default(false)->after('offers_trial_sessions');
        });

        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->boolean('is_fully_booked')->default(false)->after('available_time_end');
        });
    }

    public function down(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('is_fully_booked');
        });

        Schema::table('academic_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('is_fully_booked');
        });
    }
};
