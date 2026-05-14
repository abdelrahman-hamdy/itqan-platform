<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase C — Subscription audit log table.
 *
 * Append-only structured record of every mutator that ran against a
 * subscription. Schema mirrors docs/subscription-invariants.md §9 exactly,
 * with one deliberate deviation from the doc:
 *
 *   - The doc proposes a functional index on JSON_LENGTH(invariant_violations).
 *     MySQL 8 supports it, but Laravel's schema builder cannot express it
 *     portably and migration tooling routinely chokes on functional indexes
 *     during fresh-migrate cycles in tests.
 *
 *   - Replacement: a regular `has_violations` BOOLEAN column written by
 *     SubscriptionAuditLog::record() (true when invariant_violations is a
 *     non-empty array). Indexed normally. Query semantics are identical
 *     for the daily report (`withViolations` scope = `where has_violations
 *     = true`).
 *
 * The functional index can be reintroduced in a follow-up migration if/when
 * we need ad-hoc queries that don't go through the model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_audit_log', function (Blueprint $table) {
            $table->id();

            // Polymorphic subscription pointer (Quran / Academic / Course).
            // Stored as (subscription_id, subscription_type) — the same shape
            // used by SubscriptionCycle / payments / etc.
            $table->unsignedBigInteger('subscription_id');
            $table->string('subscription_type', 50);

            $table->unsignedBigInteger('cycle_id')->nullable();

            // 'pay', 'renew', 'resubscribe', 'pause', 'resume', 'extend',
            // 'cancel', 'advance_cycle', 'expire', 'record_consumption',
            // 'reverse_consumption', 'apply_pricing_override', etc.
            $table->string('action', 64);

            // 'web', 'api', 'cron', 'admin', 'webhook', 'job', 'console'
            $table->string('source', 32);

            $table->unsignedBigInteger('actor_user_id')->nullable();

            // Pre- and post-mutation snapshot of the SubscriptionSnapshot fields.
            // JSON, not nullable — both sides MUST be captured.
            $table->json('before_state');
            $table->json('after_state');

            // SubscriptionViewState case names (e.g. 'active_paid'). Nullable
            // until SubscriptionPresentation::viewStateFor() lands in Phase A.
            $table->string('view_state_before', 32)->nullable();
            $table->string('view_state_after', 32)->nullable();

            // Array of violations returned by SubscriptionInvariantChecker.
            // Null when the writer did not run the checker (or when checker
            // is not yet wired). Empty array = ran and passed.
            $table->json('invariant_violations')->nullable();

            // Deviation from doc §9: regular boolean column replaces the
            // proposed functional index on JSON_LENGTH(invariant_violations).
            // SubscriptionAuditLog::record() keeps this in sync with the JSON.
            $table->boolean('has_violations')->default(false);

            $table->unsignedInteger('latency_ms')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes — per doc §9 plus the has_violations swap.
            $table->index(['subscription_type', 'subscription_id', 'created_at'], 'idx_sub_created');
            $table->index('action', 'idx_action');
            $table->index('has_violations', 'idx_has_violations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_audit_log');
    }
};
