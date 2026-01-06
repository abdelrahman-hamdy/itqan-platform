<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop unused/orphaned columns from quran_subscriptions table.
 *
 * - rating/review_text: Not linked to StudentReview model
 * - created_by/updated_by: Never read in application
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, drop foreign key constraints if they exist
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $foreignKeys = [
                'quran_subscriptions_created_by_foreign',
                'quran_subscriptions_updated_by_foreign',
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
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $columnsToDrop = [
                'rating',             // Orphaned - not linked to StudentReview
                'review_text',        // Orphaned - not linked to StudentReview
                'reviewed_at',        // Related to orphaned review fields
                'created_by',         // Never read in application
                'updated_by',         // Never read in application
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('quran_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('quran_subscriptions', 'rating')) {
                $table->decimal('rating', 2, 1)->nullable();
            }
            if (! Schema::hasColumn('quran_subscriptions', 'review_text')) {
                $table->text('review_text')->nullable();
            }
            if (! Schema::hasColumn('quran_subscriptions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
            if (! Schema::hasColumn('quran_subscriptions', 'created_by')) {
                $table->foreignId('created_by')->nullable();
            }
            if (! Schema::hasColumn('quran_subscriptions', 'updated_by')) {
                $table->foreignId('updated_by')->nullable();
            }
        });
    }
};
