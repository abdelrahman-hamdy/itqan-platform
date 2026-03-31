<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds virtual columns and unique indexes to prevent duplicate pending subscriptions.
 *
 * MySQL doesn't support partial unique indexes, so we use a virtual generated column
 * that is 1 when status='pending' and NULL otherwise. NULL values are ignored by
 * unique indexes, so only pending subscriptions are constrained.
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, clean up any existing duplicate pending subscriptions
        $this->cleanupExistingDuplicates();

        // Quran subscriptions: virtual column + unique index
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->tinyInteger('is_pending_flag')
                ->virtualAs("CASE WHEN status = 'pending' AND deleted_at IS NULL THEN 1 ELSE NULL END")
                ->nullable()
                ->after('status');
        });

        DB::statement('ALTER TABLE `quran_subscriptions` ADD UNIQUE INDEX `uq_quran_pending_per_combination` (`academy_id`, `student_id`, `quran_teacher_id`, `package_id`, `is_pending_flag`)');

        // Academic subscriptions: virtual column + unique index
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->tinyInteger('is_pending_flag')
                ->virtualAs("CASE WHEN status = 'pending' AND deleted_at IS NULL THEN 1 ELSE NULL END")
                ->nullable()
                ->after('status');
        });

        DB::statement('ALTER TABLE `academic_subscriptions` ADD UNIQUE INDEX `uq_academic_pending_per_combination` (`academy_id`, `student_id`, `teacher_id`, `academic_package_id`, `is_pending_flag`)');
    }

    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropIndex('uq_quran_pending_per_combination');
            $table->dropColumn('is_pending_flag');
        });

        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $table->dropIndex('uq_academic_pending_per_combination');
            $table->dropColumn('is_pending_flag');
        });
    }

    /**
     * Clean up existing duplicate pending subscriptions before adding the unique constraint.
     * Keeps the most recent pending subscription for each combination and cancels older ones.
     */
    private function cleanupExistingDuplicates(): void
    {
        // Quran: find duplicate pending subscriptions per combination
        $quranDuplicates = DB::select("
            SELECT qs.id FROM quran_subscriptions qs
            WHERE qs.status = 'pending'
            AND qs.id NOT IN (
                SELECT MAX(qs2.id) FROM quran_subscriptions qs2
                WHERE qs2.status = 'pending'
                GROUP BY qs2.academy_id, qs2.student_id, qs2.quran_teacher_id, qs2.package_id
            )
        ");

        if (count($quranDuplicates) > 0) {
            $ids = array_column($quranDuplicates, 'id');
            DB::table('quran_subscriptions')
                ->whereIn('id', $ids)
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'تم إلغاء الاشتراك المعلق المكرر تلقائياً أثناء ترقية النظام',
                    'updated_at' => now(),
                ]);
        }

        // Academic: same cleanup
        $academicDuplicates = DB::select("
            SELECT a.id FROM academic_subscriptions a
            WHERE a.status = 'pending'
            AND a.id NOT IN (
                SELECT MAX(a2.id) FROM academic_subscriptions a2
                WHERE a2.status = 'pending'
                GROUP BY a2.academy_id, a2.student_id, a2.teacher_id, a2.academic_package_id
            )
        ");

        if (count($academicDuplicates) > 0) {
            $ids = array_column($academicDuplicates, 'id');
            DB::table('academic_subscriptions')
                ->whereIn('id', $ids)
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'تم إلغاء الاشتراك المعلق المكرر تلقائياً أثناء ترقية النظام',
                    'updated_at' => now(),
                ]);
        }
    }
};
