<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 1 Data Migration: Populate education_unit references
 *
 * This migration:
 * 1. Backfills education_unit_id/type on quran_subscriptions for individual subscriptions
 * 2. Links quran_circle_students to their corresponding quran_subscriptions for group subscriptions
 *
 * IMPORTANT: This must run AFTER the schema migration (decouple_subscriptions_from_education_units_phase1)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Backfill education_unit for INDIVIDUAL subscriptions
        // Link subscription to its QuranIndividualCircle
        $individualCount = DB::table('quran_subscriptions as qs')
            ->join('quran_individual_circles as qic', 'qic.subscription_id', '=', 'qs.id')
            ->where('qs.subscription_type', 'individual')
            ->whereNull('qs.education_unit_id')
            ->update([
                'qs.education_unit_id' => DB::raw('qic.id'),
                'qs.education_unit_type' => 'App\\Models\\QuranIndividualCircle',
            ]);

        Log::info("Backfilled education_unit for {$individualCount} individual subscriptions");

        // Step 2: Link group circle enrollments to their subscriptions
        // Match by student_id, teacher_id, and active status
        // This is more complex because group subscriptions don't have a direct link

        // First, get all group subscriptions without a linked education unit
        $groupSubscriptions = DB::table('quran_subscriptions')
            ->where('subscription_type', 'group')
            ->whereNull('education_unit_id')
            ->get();

        $linkedCount = 0;

        foreach ($groupSubscriptions as $subscription) {
            // Find matching circle enrollment for this student
            // A student can only be enrolled in one circle from the same teacher at a time
            $enrollment = DB::table('quran_circle_students as qcs')
                ->join('quran_circles as qc', 'qc.id', '=', 'qcs.circle_id')
                ->where('qcs.student_id', $subscription->student_id)
                ->where('qc.quran_teacher_id', $subscription->quran_teacher_id)
                ->where('qcs.status', 'enrolled')
                ->whereNull('qcs.subscription_id') // Not already linked
                ->first();

            if ($enrollment) {
                // Link the enrollment to this subscription
                DB::table('quran_circle_students')
                    ->where('id', $enrollment->id)
                    ->update(['subscription_id' => $subscription->id]);

                // Update the subscription's education_unit to point to the circle
                DB::table('quran_subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'education_unit_id' => $enrollment->circle_id,
                        'education_unit_type' => 'App\\Models\\QuranCircle',
                    ]);

                $linkedCount++;
            }
        }

        Log::info("Linked {$linkedCount} group circle enrollments to subscriptions");

        // Log any orphaned subscriptions that couldn't be linked
        $orphanedCount = DB::table('quran_subscriptions')
            ->whereNull('education_unit_id')
            ->count();

        if ($orphanedCount > 0) {
            Log::warning("Found {$orphanedCount} subscriptions without linked education units. These may need manual review.");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear education_unit references from subscriptions
        DB::table('quran_subscriptions')
            ->update([
                'education_unit_id' => null,
                'education_unit_type' => null,
            ]);

        // Clear subscription_id from circle enrollments
        DB::table('quran_circle_students')
            ->update(['subscription_id' => null]);

        Log::info('Cleared all education_unit and subscription links (data migration rollback)');
    }
};
