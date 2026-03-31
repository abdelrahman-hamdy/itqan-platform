<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds previous_subscription_id to all subscription tables for renewal chain tracking.
 * Links a renewed subscription to the one it replaces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_subscription_id')->nullable()->after('student_id');
            $table->foreign('previous_subscription_id', 'fk_quran_prev_sub')
                ->references('id')->on('quran_subscriptions')->nullOnDelete();
            $table->index('previous_subscription_id', 'idx_quran_prev_sub');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_subscription_id')->nullable()->after('student_id');
            $table->foreign('previous_subscription_id', 'fk_academic_prev_sub')
                ->references('id')->on('academic_subscriptions')->nullOnDelete();
            $table->index('previous_subscription_id', 'idx_academic_prev_sub');
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_subscription_id')->nullable()->after('student_id');
            $table->foreign('previous_subscription_id', 'fk_course_prev_sub')
                ->references('id')->on('course_subscriptions')->nullOnDelete();
            $table->index('previous_subscription_id', 'idx_course_prev_sub');
        });
    }

    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_quran_prev_sub');
            $table->dropColumn('previous_subscription_id');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_academic_prev_sub');
            $table->dropColumn('previous_subscription_id');
        });

        Schema::table('course_subscriptions', function (Blueprint $table) {
            $table->dropForeign('fk_course_prev_sub');
            $table->dropColumn('previous_subscription_id');
        });
    }
};
