<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align academic_subscriptions.payment_status enum to match
     * the SubscriptionPaymentStatus PHP enum (pending, paid, failed).
     *
     * Old values: current, pending, overdue, failed, refunded
     * New values: pending, paid, failed, refunded, cancelled
     */
    public function up(): void
    {
        // First convert 'current' -> 'paid' (temporary step via raw SQL)
        DB::statement("UPDATE academic_subscriptions SET payment_status = 'pending' WHERE payment_status = 'current'");
        DB::statement("UPDATE academic_subscriptions SET payment_status = 'failed' WHERE payment_status = 'overdue'");

        // Alter the enum to match SubscriptionPaymentStatus
        DB::statement("ALTER TABLE academic_subscriptions MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE academic_subscriptions SET payment_status = 'pending' WHERE payment_status = 'cancelled'");
        DB::statement("ALTER TABLE academic_subscriptions MODIFY COLUMN payment_status ENUM('current','pending','overdue','failed','refunded') NOT NULL DEFAULT 'current'");
    }
};
