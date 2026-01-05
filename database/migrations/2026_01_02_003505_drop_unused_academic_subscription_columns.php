<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused/orphaned columns from academic_subscriptions table.
 *
 * - hourly_rate: Only used once to calculate monthly_price, then discarded
 * - total_sessions_*: Set but never read (calculated from relationship instead)
 * - rating/review_text: Orphaned - not linked to StudentReview model
 * - created_by/updated_by: Never read in application
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints if they exist
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $foreignKeys = [
                'academic_subscriptions_created_by_foreign',
                'academic_subscriptions_updated_by_foreign',
            ];

            foreach ($foreignKeys as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
            }
        });

        // Now drop the columns
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $columnsToDrop = [
                'hourly_rate',              // Used once for calculation, then discarded
                'total_sessions_scheduled', // Set but never read
                'total_sessions_completed', // Calculated from relationship instead
                'total_sessions_missed',    // Set but never read
                'rating',                   // Orphaned - not linked to StudentReview
                'review_text',              // Orphaned - not linked to StudentReview
                'reviewed_at',              // Related to orphaned review fields
                'created_by',               // Never read in application
                'updated_by',               // Never read in application
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('academic_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_subscriptions', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'total_sessions_scheduled')) {
                $table->unsignedInteger('total_sessions_scheduled')->default(0);
            }
            if (!Schema::hasColumn('academic_subscriptions', 'total_sessions_completed')) {
                $table->unsignedInteger('total_sessions_completed')->default(0);
            }
            if (!Schema::hasColumn('academic_subscriptions', 'total_sessions_missed')) {
                $table->unsignedInteger('total_sessions_missed')->default(0);
            }
            if (!Schema::hasColumn('academic_subscriptions', 'rating')) {
                $table->decimal('rating', 2, 1)->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'review_text')) {
                $table->text('review_text')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (!Schema::hasColumn('academic_subscriptions', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
