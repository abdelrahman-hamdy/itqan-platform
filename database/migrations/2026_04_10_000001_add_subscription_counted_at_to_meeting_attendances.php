<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add subscription_counted_at to meeting_attendances.
 *
 * This column is already referenced in SessionCountingService and
 * CountsTowardsSubscription::updateGroupSubscriptionUsage() as an
 * idempotency guard, and it exists on the legacy *_session_attendances
 * tables via the earlier migration 2026_04_07_000002. It was missed on
 * meeting_attendances when its counting flags were added, which caused
 * writes via the query builder to throw "Unknown column" and roll back
 * the enclosing transaction (blocking session counting entirely).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('meeting_attendances', 'subscription_counted_at')) {
            return;
        }

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->timestamp('subscription_counted_at')
                ->nullable()
                ->after('counts_for_subscription_set_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('meeting_attendances', 'subscription_counted_at')) {
            return;
        }

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropColumn('subscription_counted_at');
        });
    }
};
