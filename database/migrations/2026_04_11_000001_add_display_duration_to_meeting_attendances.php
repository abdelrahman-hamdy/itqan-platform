<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add display_duration_minutes to meeting_attendances.
 *
 * Holds uncapped "time inside the meeting window" (prep + session + buffer)
 * for UI display. Status calculation still uses total_duration_minutes
 * (capped to [scheduled_at, scheduled_at + duration_minutes]).
 *
 * No backfill: historical rows default to 0 and are never revisited by the
 * new calc path (blocked by matrix_cutoff_at). A table-wide UPDATE here
 * would hold a multi-minute lock on meeting_attendances.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('meeting_attendances', 'display_duration_minutes')) {
            return;
        }

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->integer('display_duration_minutes')
                ->default(0)
                ->after('total_duration_minutes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('meeting_attendances', 'display_duration_minutes')) {
            return;
        }

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropColumn('display_duration_minutes');
        });
    }
};
