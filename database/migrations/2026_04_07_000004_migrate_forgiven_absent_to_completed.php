<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migrate FORGIVEN and ABSENT sessions to COMPLETED status.
 *
 * Replaces the FORGIVEN/ABSENT session statuses with counting flags:
 * - FORGIVEN → COMPLETED with counts_for_teacher=false, counts_for_subscription=false
 * - ABSENT → COMPLETED (preserves existing subscription_counted state)
 *
 * Then removes 'forgiven' and 'absent' from the MySQL ENUM.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];

        foreach ($tables as $table) {
            // 1. Migrate FORGIVEN → COMPLETED
            $forgivenCount = DB::table($table)->where('status', 'forgiven')->count();
            if ($forgivenCount > 0) {
                DB::table($table)
                    ->where('status', 'forgiven')
                    ->update([
                        'status' => 'completed',
                        'counts_for_teacher' => false,
                    ]);

                Log::info("Migrated {$forgivenCount} FORGIVEN sessions to COMPLETED in {$table}");
            }

            // 2. Migrate ABSENT → COMPLETED
            $absentCount = DB::table($table)->where('status', 'absent')->count();
            if ($absentCount > 0) {
                DB::table($table)
                    ->where('status', 'absent')
                    ->update([
                        'status' => 'completed',
                    ]);

                Log::info("Migrated {$absentCount} ABSENT sessions to COMPLETED in {$table}");
            }

            // 3. Shrink the ENUM (remove forgiven and absent)
            $newEnum = "'unscheduled','scheduled','ready','ongoing','completed','cancelled','suspended'";
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM({$newEnum}) NOT NULL DEFAULT 'unscheduled'");
        }
    }

    public function down(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];
        $fullEnum = "'unscheduled','scheduled','ready','ongoing','completed','cancelled','suspended','absent','forgiven'";

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM({$fullEnum}) NOT NULL DEFAULT 'unscheduled'");
        }
    }
};
