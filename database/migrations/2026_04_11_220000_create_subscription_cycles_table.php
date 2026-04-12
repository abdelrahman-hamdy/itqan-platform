<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the polymorphic `subscription_cycles` table that stores per-billing-period
 * snapshots of a subscription thread (window, price, package, payment, counters).
 *
 * A subscription row is a persistent thread; each cycle is one billing period.
 * At most one `active` cycle per subscription, at most one `queued`, any number `archived`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_cycles', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner (QuranSubscription or AcademicSubscription)
            $table->string('subscribable_type');
            $table->unsignedBigInteger('subscribable_id');

            // Tenant scoping
            $table->unsignedBigInteger('academy_id');

            // Cycle identity within the thread
            $table->unsignedInteger('cycle_number');
            $table->enum('cycle_state', ['queued', 'active', 'archived'])->default('queued');

            // Billing window
            $table->string('billing_cycle', 32);  // monthly/quarterly/yearly
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Session counters for this cycle
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('sessions_used')->default(0);
            $table->unsignedInteger('sessions_completed')->default(0);
            $table->unsignedInteger('sessions_missed')->default(0);
            $table->unsignedInteger('carryover_sessions')->default(0);

            // Pricing snapshot
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->string('currency', 8)->default('SAR');

            // Package snapshot
            $table->unsignedBigInteger('package_id')->nullable();
            $table->json('package_snapshot')->nullable();

            // Payment linkage
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'waived'])->default('pending');

            // Grace period window (per-cycle, not per-subscription)
            $table->timestamp('grace_period_ends_at')->nullable();

            // Lifecycle markers
            $table->timestamp('archived_at')->nullable();

            // Misc
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['subscribable_type', 'subscribable_id', 'cycle_number'],
                'sub_cycles_thread_number_unique'
            );
            $table->index(
                ['subscribable_type', 'subscribable_id', 'cycle_state'],
                'sub_cycles_thread_state_idx'
            );
            // Used by `subscriptions:advance-cycles` (hourly): finds active
            // cycles whose ends_at has passed so a queued cycle can replace them.
            $table->index(['cycle_state', 'ends_at'], 'sub_cycles_state_ends_idx');
            $table->index('academy_id', 'sub_cycles_academy_idx');
            $table->index('grace_period_ends_at', 'sub_cycles_grace_idx');

            $table->foreign('academy_id', 'sub_cycles_academy_fk')
                ->references('id')->on('academies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_cycles');
    }
};
