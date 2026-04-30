<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repair JSON columns that were double-encoded by code that called
     * json_encode() on a value before assigning it to an attribute already
     * cast as 'array'. The cast then encoded again, so each affected row
     * stores a JSON-encoded string at the top level (JSON_TYPE = 'STRING')
     * instead of an array/object. On read, the cast decodes once and
     * returns the inner string, breaking any in_array / array-offset usage.
     *
     * Matched code sites are now fixed:
     *   - app/Models/VideoSettings.php (blocked_days, notification_channels)
     *   - app/Models/Payment.php (metadata, via createPayment())
     *   - app/Http/Middleware/EnsureSubscriptionAccess.php (metadata)
     *   - app/Http/Controllers/Api/V1/Student/MobilePurchaseController.php (metadata)
     *
     * This migration unwraps existing corrupted rows in place. It is
     * idempotent: re-running it touches no rows once the data is correct.
     */
    public function up(): void
    {
        $columns = [
            ['video_settings', 'blocked_days'],
            ['video_settings', 'notification_channels'],
            ['payments', 'metadata'],
            ['subscription_access_logs', 'metadata'],
        ];

        // Chunked so the 95k-row video_settings updates don't hold row locks
        // long enough to contend with concurrent firstOrCreate() traffic from
        // AutoMeetingCreationService during deploy.
        $batchSize = 2000;

        foreach ($columns as [$table, $column]) {
            do {
                $affected = DB::update("
                    UPDATE `{$table}`
                    SET `{$column}` = CAST(JSON_UNQUOTE(`{$column}`) AS JSON)
                    WHERE `{$column}` IS NOT NULL
                      AND JSON_TYPE(`{$column}`) = 'STRING'
                      AND JSON_VALID(JSON_UNQUOTE(`{$column}`))
                    LIMIT {$batchSize}
                ");
            } while ($affected >= $batchSize);
        }
    }

    public function down(): void
    {
        // Intentionally a no-op. Re-corrupting rows on rollback would
        // be data loss; repaired rows are correct regardless of which
        // version of the application code is deployed.
    }
};
