<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add 'suspended' to session status enum columns.
 *
 * SUSPENDED means "session held due to subscription expiry/pause" —
 * recoverable when subscription is resumed/reactivated.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];
        $newEnum = "'unscheduled','scheduled','ready','ongoing','completed','cancelled','suspended','absent','forgiven'";

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM({$newEnum}) NOT NULL DEFAULT 'unscheduled'");
        }
    }

    public function down(): void
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];
        $oldEnum = "'unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','forgiven'";

        foreach ($tables as $table) {
            // First move any 'suspended' back to 'cancelled' so the ALTER doesn't fail
            DB::table($table)->where('status', 'suspended')->update(['status' => 'cancelled']);
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM({$oldEnum}) NOT NULL DEFAULT 'unscheduled'");
        }
    }
};
