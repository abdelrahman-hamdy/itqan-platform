<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add 'forgiven' status to session tables and forgiveness tracking fields.
 *
 * FORGIVEN = admin manually pardons an ABSENT session so it no longer
 * counts against the student's subscription or toward teacher earnings.
 */
return new class extends Migration
{
    private array $tables = [
        'quran_sessions',
        'academic_sessions',
        'interactive_course_sessions',
    ];

    public function up(): void
    {
        // 1. Add 'forgiven' to the status enum on each session table
        foreach ($this->tables as $table) {
            DB::statement(
                "ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM(
                    'unscheduled','scheduled','ready','ongoing',
                    'completed','cancelled','absent','forgiven'
                ) NOT NULL DEFAULT 'unscheduled'"
            );
        }

        // 2. Add forgiveness tracking columns
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->timestamp('forgiven_at')->nullable()->after('cancelled_at');
                $blueprint->unsignedBigInteger('forgiven_by')->nullable()->after('forgiven_at');
                $blueprint->text('forgiven_reason')->nullable()->after('forgiven_by');

                $blueprint->foreign('forgiven_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // 1. Remove forgiveness columns
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign(["{$table}_forgiven_by_foreign"]);
                $blueprint->dropColumn(['forgiven_at', 'forgiven_by', 'forgiven_reason']);
            });
        }

        // 2. Revert any FORGIVEN rows back to ABSENT before shrinking the enum
        foreach ($this->tables as $table) {
            DB::table($table)->where('status', 'forgiven')->update(['status' => 'absent']);
        }

        // 3. Remove 'forgiven' from the enum
        foreach ($this->tables as $table) {
            DB::statement(
                "ALTER TABLE `{$table}` MODIFY COLUMN `status` ENUM(
                    'unscheduled','scheduled','ready','ongoing',
                    'completed','cancelled','absent'
                ) NOT NULL DEFAULT 'unscheduled'"
            );
        }
    }
};
