<?php

use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix QuranSubscriptions that have an individual circle via legacy subscription_id FK
 * but are missing the polymorphic education_unit_id/education_unit_type columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fixed = 0;
        $orphaned = 0;

        // Find subscriptions missing education_unit_id but having a circle via legacy FK
        $subscriptions = QuranSubscription::withoutGlobalScopes()
            ->whereNull('education_unit_id')
            ->where('subscription_type', 'individual')
            ->get();

        foreach ($subscriptions as $sub) {
            $circle = QuranIndividualCircle::where('subscription_id', $sub->id)->first();

            if ($circle) {
                $sub->update([
                    'education_unit_id' => $circle->id,
                    'education_unit_type' => 'individual_circle', // morph alias
                ]);

                Log::info('Fixed subscription education_unit link', [
                    'subscription_id' => $sub->id,
                    'subscription_code' => $sub->subscription_code,
                    'circle_id' => $circle->id,
                    'circle_code' => $circle->circle_code,
                ]);

                $fixed++;
            } else {
                $orphaned++;
            }
        }

        Log::info("Migration complete: fixed {$fixed} subscriptions, {$orphaned} still without circles.");
    }

    public function down(): void
    {
        // No rollback needed — this is a data fix
    }
};
