<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add student preference fields to quran_subscriptions for individual subscriptions.
     * These fields allow students to specify their scheduling and learning preferences.
     * For group/circle subscriptions, these fields will remain null.
     */
    public function up(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            // Student preferences (same as AcademicSubscription)
            $table->json('weekly_schedule')->nullable()->after('metadata');
            $table->text('student_notes')->nullable()->after('weekly_schedule');
            $table->json('learning_goals')->nullable()->after('student_notes');
            $table->json('preferred_times')->nullable()->after('learning_goals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'weekly_schedule',
                'student_notes',
                'learning_goals',
                'preferred_times',
            ]);
        });
    }
};
