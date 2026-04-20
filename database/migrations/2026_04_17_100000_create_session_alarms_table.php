<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session alarm audit log.
 *
 * Records every alarm ("ring the other participant") attempt — answered,
 * declined, or left ringing. Powers the 30s-per-pair cooldown and gives
 * supervisors a trail for abuse detection.
 *
 * - session_type/session_id pair is polymorphic so we can alarm across Quran,
 *   Academic, and Interactive sessions without a separate table per type.
 * - caller_id / target_id both reference users.id; on cascade we want to keep
 *   the audit row even if the caller is deleted, hence nullOnDelete.
 * - call_id is the correlation ID shared with the mobile CallKit UI so
 *   `cancelCall(call_id)` can dismiss all rings when one device answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_alarms', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_id')->unique();

            $table->string('session_type', 50);
            $table->unsignedBigInteger('session_id');

            $table->foreignId('caller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->index(['session_type', 'session_id']);
            $table->index(['caller_id', 'target_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_alarms');
    }
};
