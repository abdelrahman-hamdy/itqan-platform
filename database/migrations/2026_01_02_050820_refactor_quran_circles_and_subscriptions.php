<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor Quran Circles and Subscriptions
 *
 * This migration:
 * 1. QuranIndividualCircle: Replace legacy progress fields with homework-based fields
 * 2. QuranIndividualCircle: Remove recording_enabled (not needed for individual circles)
 * 3. QuranCircle (Group): Add same homework-based progress fields for consistency
 * 4. QuranSubscription: Add pause support columns, admin/supervisor notes, simplify pricing
 * 5. QuranSubscription: Remove progress fields (belong on Circle, not Subscription)
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // 1. QuranIndividualCircle Changes
        // =====================================================
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Add new homework-based progress fields
            if (! Schema::hasColumn('quran_individual_circles', 'total_memorized_pages')) {
                $table->unsignedInteger('total_memorized_pages')->default(0)->after('progress_percentage');
            }
            if (! Schema::hasColumn('quran_individual_circles', 'total_reviewed_pages')) {
                $table->unsignedInteger('total_reviewed_pages')->default(0)->after('total_memorized_pages');
            }
            if (! Schema::hasColumn('quran_individual_circles', 'total_reviewed_surahs')) {
                $table->unsignedInteger('total_reviewed_surahs')->default(0)->after('total_reviewed_pages');
            }
        });

        // Drop old progress fields (in separate statement to avoid issues)
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $columnsToDrop = [
                'current_surah',
                'current_page',
                'current_face',
                'papers_memorized',
                'papers_memorized_precise',
                'progress_percentage',
                'recording_enabled',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('quran_individual_circles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // =====================================================
        // 2. QuranCircle (Group) Changes
        // =====================================================
        Schema::table('quran_circles', function (Blueprint $table) {
            // Add homework-based progress fields for consistency with individual circles
            if (! Schema::hasColumn('quran_circles', 'total_memorized_pages')) {
                $table->unsignedInteger('total_memorized_pages')->default(0)->after('sessions_completed');
            }
            if (! Schema::hasColumn('quran_circles', 'total_reviewed_pages')) {
                $table->unsignedInteger('total_reviewed_pages')->default(0)->after('total_memorized_pages');
            }
            if (! Schema::hasColumn('quran_circles', 'total_reviewed_surahs')) {
                $table->unsignedInteger('total_reviewed_surahs')->default(0)->after('total_reviewed_pages');
            }
        });

        // =====================================================
        // 3. QuranSubscription Changes
        // =====================================================
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            // Add pause support columns (fixes critical bug in pause action)
            if (! Schema::hasColumn('quran_subscriptions', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('quran_subscriptions', 'pause_reason')) {
                $table->text('pause_reason')->nullable()->after('paused_at');
            }

            // Add split notes fields
            if (! Schema::hasColumn('quran_subscriptions', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('quran_subscriptions', 'supervisor_notes')) {
                $table->text('supervisor_notes')->nullable()->after('admin_notes');
            }
        });

        // Drop over-engineered and misplaced fields from subscription
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $columnsToDrop = [
                'discount_amount',
                'final_price',
                'pages_memorized',
                'progress_percentage',
                'current_surah',
                'current_page',
                'memorization_level',
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
        // Restore QuranSubscription columns
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            // Remove new columns
            $columnsToRemove = ['paused_at', 'pause_reason', 'admin_notes', 'supervisor_notes'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('quran_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Restore dropped columns
            if (! Schema::hasColumn('quran_subscriptions', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('quran_subscriptions', 'final_price')) {
                $table->decimal('final_price', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('quran_subscriptions', 'progress_percentage')) {
                $table->decimal('progress_percentage', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('quran_subscriptions', 'current_surah')) {
                $table->unsignedSmallInteger('current_surah')->nullable();
            }
            if (! Schema::hasColumn('quran_subscriptions', 'memorization_level')) {
                $table->string('memorization_level')->nullable();
            }
        });

        // Restore QuranCircle columns
        Schema::table('quran_circles', function (Blueprint $table) {
            $columnsToRemove = ['total_memorized_pages', 'total_reviewed_pages', 'total_reviewed_surahs'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('quran_circles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Restore QuranIndividualCircle columns
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $columnsToRemove = ['total_memorized_pages', 'total_reviewed_pages', 'total_reviewed_surahs'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('quran_individual_circles', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Restore dropped columns
            if (! Schema::hasColumn('quran_individual_circles', 'current_surah')) {
                $table->unsignedSmallInteger('current_surah')->nullable();
            }
            if (! Schema::hasColumn('quran_individual_circles', 'current_page')) {
                $table->unsignedSmallInteger('current_page')->nullable();
            }
            if (! Schema::hasColumn('quran_individual_circles', 'current_face')) {
                $table->unsignedSmallInteger('current_face')->nullable();
            }
            if (! Schema::hasColumn('quran_individual_circles', 'papers_memorized')) {
                $table->unsignedInteger('papers_memorized')->default(0);
            }
            if (! Schema::hasColumn('quran_individual_circles', 'papers_memorized_precise')) {
                $table->decimal('papers_memorized_precise', 8, 2)->default(0);
            }
            if (! Schema::hasColumn('quran_individual_circles', 'progress_percentage')) {
                $table->decimal('progress_percentage', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('quran_individual_circles', 'recording_enabled')) {
                $table->boolean('recording_enabled')->default(false);
            }
        });
    }
};
