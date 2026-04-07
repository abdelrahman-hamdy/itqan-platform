<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add counting flags to meeting_attendances table.
 * This is the actual source of truth for attendance data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->boolean('counts_for_subscription')->nullable()->default(null)->after('attendance_status');
            $table->unsignedBigInteger('counts_for_subscription_set_by')->nullable()->after('counts_for_subscription');
            $table->timestamp('counts_for_subscription_set_at')->nullable()->after('counts_for_subscription_set_by');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropColumn(['counts_for_subscription', 'counts_for_subscription_set_by', 'counts_for_subscription_set_at']);
        });
    }
};
