<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds tenant scoping to `session_alarms` so admin/analytics queries don't
 * leak audit rows across academies. Backfills `academy_id` from the caller
 * user before introducing the NOT NULL constraint via a follow-up safer
 * approach: add nullable, backfill, then leave nullable to avoid breaking
 * any in-flight transactions during the deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_alarms', function (Blueprint $table) {
            $table->foreignId('academy_id')
                ->nullable()
                ->after('id')
                ->constrained('academies')
                ->nullOnDelete();
            $table->index(['academy_id', 'created_at']);
        });

        // Backfill from the caller's academy. SessionAlarm rows without a
        // resolvable caller (caller_id is nullOnDelete) stay null and remain
        // visible only to super-admin global view.
        DB::statement(<<<'SQL'
            UPDATE session_alarms sa
            INNER JOIN users u ON u.id = sa.caller_id
            SET sa.academy_id = u.academy_id
            WHERE sa.academy_id IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('session_alarms', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropIndex(['academy_id', 'created_at']);
            $table->dropColumn('academy_id');
        });
    }
};
