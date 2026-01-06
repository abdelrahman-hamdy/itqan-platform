<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: Decouple Subscriptions from Education Units
 *
 * This migration:
 * 1. Makes subscription_id nullable on quran_individual_circles (allowing circles to exist independently)
 * 2. Changes the foreign key from CASCADE to SET NULL (subscription deletion won't delete circles)
 * 3. Adds polymorphic education_unit columns to quran_subscriptions (for explicit unit linking)
 * 4. Adds subscription_id to quran_circle_students (for group subscription tracking)
 *
 * IMPORTANT: Run data migration after this to populate education_unit references
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Make subscription_id nullable on quran_individual_circles
        // This allows circles to exist independently from subscriptions
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // First, drop the existing foreign key constraint
            $table->dropForeign(['subscription_id']);
        });

        // Change column to nullable
        DB::statement('ALTER TABLE quran_individual_circles MODIFY subscription_id BIGINT UNSIGNED NULL');

        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Re-add foreign key with SET NULL instead of CASCADE
            $table->foreign('subscription_id')
                ->references('id')
                ->on('quran_subscriptions')
                ->onDelete('set null');
        });

        // Step 2: Add polymorphic education_unit columns to quran_subscriptions
        // This allows subscriptions to explicitly reference their education unit
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->nullableMorphs('education_unit');
        });

        // Step 3: Add subscription_id to quran_circle_students for group subscription tracking
        // This allows tracking which subscription a group enrollment belongs to
        if (! Schema::hasColumn('quran_circle_students', 'subscription_id')) {
            Schema::table('quran_circle_students', function (Blueprint $table) {
                $table->foreignId('subscription_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('quran_subscriptions')
                    ->nullOnDelete();
            });
        }

        // Step 4: Add soft deletes to quran_circle_students if not present
        if (! Schema::hasColumn('quran_circle_students', 'deleted_at')) {
            Schema::table('quran_circle_students', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes from quran_circle_students
        if (Schema::hasColumn('quran_circle_students', 'deleted_at')) {
            Schema::table('quran_circle_students', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove subscription_id from quran_circle_students
        if (Schema::hasColumn('quran_circle_students', 'subscription_id')) {
            Schema::table('quran_circle_students', function (Blueprint $table) {
                $table->dropForeign(['subscription_id']);
                $table->dropColumn('subscription_id');
            });
        }

        // Remove polymorphic columns from quran_subscriptions
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropMorphs('education_unit');
        });

        // Restore subscription_id as NOT NULL on quran_individual_circles
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
        });

        // Note: This will fail if there are NULL values
        DB::statement('ALTER TABLE quran_individual_circles MODIFY subscription_id BIGINT UNSIGNED NOT NULL');

        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->foreign('subscription_id')
                ->references('id')
                ->on('quran_subscriptions')
                ->onDelete('cascade');
        });
    }
};
