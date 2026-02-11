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
        Schema::table('payments', function (Blueprint $table) {
            // Track when payment notification was sent (prevents duplicates)
            $table->timestamp('payment_notification_sent_at')->nullable()->after('paid_at');

            // Track when subscription activation notification was sent (prevents duplicates)
            $table->timestamp('subscription_notification_sent_at')->nullable()->after('payment_notification_sent_at');

            // Index for finding payments that need notification (missed webhooks)
            $table->index(['status', 'payment_notification_sent_at', 'paid_at'], 'idx_payment_notification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payment_notification_status');
            $table->dropColumn(['payment_notification_sent_at', 'subscription_notification_sent_at']);
        });
    }
};
