<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-001: Add index on meeting_room_name for faster webhook lookups.
 *
 * The LiveKit webhook controller queries meeting_room_name on every webhook event
 * (participant join/leave, room start/finish). Without an index, each event
 * triggers a full table scan on up to 3 session tables.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->index('meeting_room_name');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->index('meeting_room_name');
        });

        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->index('meeting_room_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropIndex(['meeting_room_name']);
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndex(['meeting_room_name']);
        });

        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            $table->dropIndex(['meeting_room_name']);
        });
    }
};
