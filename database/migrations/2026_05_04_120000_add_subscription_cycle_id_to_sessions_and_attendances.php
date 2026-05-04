<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anchor every session and attendance row to the subscription cycle it
 * belongs to. Without this column, counter mutations route to whichever
 * cycle is currently active — which leaks across cycle boundaries when an
 * archived-cycle session is counted/reverted after promotion.
 *
 * Backfill is handled by `subscriptions:backfill-session-cycles` (separate
 * artisan command). New rows are stamped on creation by the session
 * observers.
 *
 * `interactive_course_sessions` is intentionally excluded:
 * `InteractiveCourseSession` does not use the `CountsTowardsSubscription`
 * trait — `CourseSubscription` is enrollment-based, not session-counted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_cycle_id')->nullable()->after('quran_subscription_id');

            $table->foreign('subscription_cycle_id', 'fk_quran_sessions_subscription_cycle')
                ->references('id')->on('subscription_cycles')
                ->nullOnDelete();
            $table->index(['subscription_cycle_id', 'status'], 'idx_quran_sessions_cycle_status');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_cycle_id')->nullable()->after('academic_subscription_id');

            $table->foreign('subscription_cycle_id', 'fk_academic_sessions_subscription_cycle')
                ->references('id')->on('subscription_cycles')
                ->nullOnDelete();
            $table->index(['subscription_cycle_id', 'status'], 'idx_academic_sessions_cycle_status');
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_cycle_id')->nullable()->after('user_id');

            $table->foreign('subscription_cycle_id', 'fk_attendances_subscription_cycle')
                ->references('id')->on('subscription_cycles')
                ->nullOnDelete();
            $table->index(['subscription_cycle_id', 'user_id'], 'idx_attendances_cycle_user');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropForeign('fk_attendances_subscription_cycle');
            $table->dropIndex('idx_attendances_cycle_user');
            $table->dropColumn('subscription_cycle_id');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropForeign('fk_academic_sessions_subscription_cycle');
            $table->dropIndex('idx_academic_sessions_cycle_status');
            $table->dropColumn('subscription_cycle_id');
        });

        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropForeign('fk_quran_sessions_subscription_cycle');
            $table->dropIndex('idx_quran_sessions_cycle_status');
            $table->dropColumn('subscription_cycle_id');
        });
    }
};
