<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add per-student counting flags to session attendance tables.
 *
 * New columns:
 * - counts_for_subscription: Admin-overridable flag controlling subscription counting (null=auto)
 * - counts_for_subscription_set_by: Audit trail for who changed the flag
 * - counts_for_subscription_set_at: When the flag was changed
 * - subscription_counted_at: When subscription was actually decremented (idempotency guard)
 *
 * Also adds 'partially_attended' to attendance_status ENUM on all relevant tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add counting flags to session attendance tables
        $attendanceTables = [
            'quran_session_attendances',
            'academic_session_attendances',
            'interactive_session_attendances',
        ];

        foreach ($attendanceTables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'counts_for_subscription')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->boolean('counts_for_subscription')->nullable()->default(null)->after('notes');
                $t->unsignedBigInteger('counts_for_subscription_set_by')->nullable()->after('counts_for_subscription');
                $t->timestamp('counts_for_subscription_set_at')->nullable()->after('counts_for_subscription_set_by');
                $t->timestamp('subscription_counted_at')->nullable()->after('counts_for_subscription_set_at');

                $fkName = substr($table, 0, 30).'_sub_set_by_fk';
                $t->foreign('counts_for_subscription_set_by', $fkName)
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        // 2. Add 'partially_attended' to attendance_status ENUM on all attendance/report tables
        $enumTables = [
            'quran_session_attendances',
            'academic_session_attendances',
            'interactive_session_attendances',
            'student_session_reports',
            'academic_session_reports',
            'interactive_session_reports',
            'meeting_attendances',
        ];

        $newEnum = "'attended','late','left','absent','partially_attended'";

        foreach ($enumTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'attendance_status')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `attendance_status` ENUM({$newEnum}) NULL DEFAULT NULL");
            }
        }
    }

    public function down(): void
    {
        $attendanceTables = [
            'quran_session_attendances',
            'academic_session_attendances',
            'interactive_session_attendances',
        ];

        foreach ($attendanceTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $fkName = substr($table, 0, 30).'_sub_set_by_fk';
                try {
                    $t->dropForeign($fkName);
                } catch (\Exception) {
                    // FK may not exist
                }

                $t->dropColumn([
                    'counts_for_subscription',
                    'counts_for_subscription_set_by',
                    'counts_for_subscription_set_at',
                    'subscription_counted_at',
                ]);
            });
        }

        // Revert ENUM (remove partially_attended)
        $enumTables = [
            'quran_session_attendances',
            'academic_session_attendances',
            'interactive_session_attendances',
            'student_session_reports',
            'academic_session_reports',
            'interactive_session_reports',
            'meeting_attendances',
        ];

        $oldEnum = "'attended','late','left','absent'";

        foreach ($enumTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'attendance_status')) {
                continue;
            }

            DB::table($table)->where('attendance_status', 'partially_attended')->update(['attendance_status' => 'late']);
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `attendance_status` ENUM({$oldEnum}) NULL DEFAULT NULL");
        }
    }
};
