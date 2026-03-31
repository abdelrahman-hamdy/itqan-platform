<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix subscriptions that were incorrectly PAUSED when sessions ran out.
 *
 * The old logic auto-paused subscriptions when sessions_remaining <= 0.
 * The new logic keeps them ACTIVE and sets metadata['sessions_exhausted'] = true.
 * This migration converts existing wrongly-paused subscriptions to the new format.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pauseReason = 'انتهت الجلسات المتاحة - في انتظار التجديد';
        $tables = ['quran_subscriptions', 'academic_subscriptions'];

        foreach ($tables as $table) {
            $paused = DB::table($table)
                ->where('status', 'paused')
                ->where('pause_reason', $pauseReason)
                ->get();

            $fixedCount = 0;

            foreach ($paused as $sub) {
                $metadata = json_decode($sub->metadata ?? '{}', true) ?: [];
                $metadata['sessions_exhausted'] = true;
                $metadata['sessions_exhausted_at'] = $sub->paused_at ?? now()->toDateTimeString();

                // Determine new status: ACTIVE if subscription period hasn't ended, EXPIRED otherwise
                $newStatus = 'active';
                if ($sub->ends_at && now()->gt($sub->ends_at)) {
                    $newStatus = 'expired';
                }

                DB::table($table)
                    ->where('id', $sub->id)
                    ->update([
                        'status' => $newStatus,
                        'paused_at' => null,
                        'pause_reason' => null,
                        'metadata' => json_encode($metadata),
                        'updated_at' => now(),
                    ]);

                $fixedCount++;
            }

            if ($fixedCount > 0) {
                Log::info("Fixed {$fixedCount} wrongly-paused subscriptions in {$table}");
            }
        }
    }

    public function down(): void
    {
        // This migration fixes data — reversal would require restoring the old pause state
        // which is not desirable. No-op for safety.
    }
};
