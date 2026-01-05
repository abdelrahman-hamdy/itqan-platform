<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refactor Subscription Status Enums
 *
 * This migration simplifies the subscription status enums:
 *
 * 1. SubscriptionStatus (9 values) → SessionSubscriptionStatus (4 values)
 *    - pending → pending
 *    - active, trial → active
 *    - paused, suspended → paused
 *    - expired, refunded, completed, cancelled → cancelled
 *
 * 2. EnrollmentStatus (7 values) → EnrollmentStatus (4 values)
 *    - pending → pending
 *    - enrolled, active → enrolled
 *    - completed → completed
 *    - dropped, suspended, expelled, cancelled → cancelled
 *
 * 3. SubscriptionPaymentStatus (4 values) → SubscriptionPaymentStatus (3 values)
 *    - pending → pending
 *    - paid → paid
 *    - failed, refunded → failed
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =============================================
        // 1. QURAN SUBSCRIPTIONS
        // =============================================
        if ($this->tableExists('quran_subscriptions')) {
            // Convert status values
            DB::statement("UPDATE quran_subscriptions SET status = CASE
                WHEN status IN ('active', 'trial') THEN 'active'
                WHEN status IN ('paused', 'suspended') THEN 'paused'
                WHEN status IN ('expired', 'refunded', 'completed', 'cancelled') THEN 'cancelled'
                ELSE status
            END");

            // Convert payment_status values
            DB::statement("UPDATE quran_subscriptions SET payment_status = 'failed'
                WHERE payment_status = 'refunded'");
        }

        // =============================================
        // 2. ACADEMIC SUBSCRIPTIONS
        // =============================================
        if ($this->tableExists('academic_subscriptions')) {
            // First, change status column from ENUM to VARCHAR to allow pending value
            // Current ENUM: ('active','paused','suspended','cancelled','expired','completed')
            DB::statement("ALTER TABLE academic_subscriptions MODIFY COLUMN status VARCHAR(50) NULL");

            // Convert status values
            DB::statement("UPDATE academic_subscriptions SET status = CASE
                WHEN status IN ('active', 'trial') THEN 'active'
                WHEN status IN ('paused', 'suspended') THEN 'paused'
                WHEN status IN ('expired', 'refunded', 'completed', 'cancelled') THEN 'cancelled'
                ELSE status
            END");

            // Convert payment_status values
            DB::statement("UPDATE academic_subscriptions SET payment_status = 'failed'
                WHERE payment_status = 'refunded'");
        }

        // =============================================
        // 3. INTERACTIVE COURSE ENROLLMENTS
        // =============================================
        if ($this->tableExists('interactive_course_enrollments')) {
            // Convert enrollment_status values to simplified EnrollmentStatus
            // Note: This table uses 'enrollment_status' column, not 'status'
            if (\Illuminate\Support\Facades\Schema::hasColumn('interactive_course_enrollments', 'enrollment_status')) {
                // First, change column from ENUM to VARCHAR to allow 'cancelled' value
                // Current ENUM: ('enrolled','dropped','completed','expelled')
                DB::statement("ALTER TABLE interactive_course_enrollments MODIFY COLUMN enrollment_status VARCHAR(50) NULL");

                DB::statement("UPDATE interactive_course_enrollments SET enrollment_status = CASE
                    WHEN enrollment_status IN ('enrolled', 'active') THEN 'enrolled'
                    WHEN enrollment_status = 'completed' THEN 'completed'
                    WHEN enrollment_status IN ('dropped', 'suspended', 'expelled', 'cancelled') THEN 'cancelled'
                    ELSE enrollment_status
                END");
            }

            // Convert payment_status values if column exists
            $this->updatePaymentStatusIfExists('interactive_course_enrollments');
        }

        // =============================================
        // 4. COURSE SUBSCRIPTIONS (Recorded Courses)
        // =============================================
        if ($this->tableExists('course_subscriptions')) {
            // First, change status column from ENUM to VARCHAR to allow new values
            // The column might be ENUM('active','completed','paused','expired','cancelled','refunded')
            DB::statement("ALTER TABLE course_subscriptions MODIFY COLUMN status VARCHAR(50) NULL");

            // Convert status values to simplified EnrollmentStatus
            DB::statement("UPDATE course_subscriptions SET status = CASE
                WHEN status IN ('enrolled', 'active') THEN 'enrolled'
                WHEN status = 'completed' THEN 'completed'
                WHEN status IN ('dropped', 'suspended', 'expelled', 'cancelled', 'expired', 'refunded', 'paused') THEN 'cancelled'
                ELSE status
            END");

            // Convert payment_status values if column exists
            $this->updatePaymentStatusIfExists('course_subscriptions');
        }

        // =============================================
        // 5. QURAN CIRCLE STUDENTS (uses EnrollmentStatus)
        // =============================================
        if ($this->tableExists('quran_circle_students')) {
            // First, change status column from ENUM to VARCHAR to allow 'cancelled' value
            // Current ENUM: ('enrolled','completed','dropped','suspended','transferred')
            DB::statement("ALTER TABLE quran_circle_students MODIFY COLUMN status VARCHAR(50) NULL");

            DB::statement("UPDATE quran_circle_students SET status = CASE
                WHEN status IN ('enrolled', 'active') THEN 'enrolled'
                WHEN status = 'completed' THEN 'completed'
                WHEN status IN ('dropped', 'suspended', 'expelled', 'cancelled', 'transferred') THEN 'cancelled'
                ELSE status
            END");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration only converts string values.
        // The reverse migration cannot fully restore original values
        // because the conversion is lossy (multiple values → single value).

        // Best effort: Keep values as-is (they're still valid strings)
        // The enum PHP classes will need to be reverted separately if needed.
    }

    /**
     * Check if table exists
     */
    private function tableExists(string $table): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    }

    /**
     * Update payment_status column if it exists in the table
     */
    private function updatePaymentStatusIfExists(string $table): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'payment_status')) {
            DB::statement("UPDATE {$table} SET payment_status = 'failed'
                WHERE payment_status = 'refunded'");
        }
    }
};
