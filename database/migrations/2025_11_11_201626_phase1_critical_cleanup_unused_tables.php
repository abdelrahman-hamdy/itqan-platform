<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1 Critical Cleanup:
     * - Drop test_livekit_session (test data)
     * - Drop academic_progresses (duplicate, 0 records)
     * - Drop service_requests (empty stub, model deleted)
     * - Drop Google integration tables (unused feature)
     */
    public function up(): void
    {
        // Drop test data table
        Schema::dropIfExists('test_livekit_session');

        // Drop duplicate academic progress table (empty, unused)
        Schema::dropIfExists('academic_progresses');

        // Drop empty service_requests table (model deleted, only 3 cols, 0 records)
        Schema::dropIfExists('service_requests');

        // Drop Google integration tables (feature not implemented)
        Schema::dropIfExists('google_tokens');
        Schema::dropIfExists('platform_google_accounts');
        Schema::dropIfExists('academy_google_settings');
    }

    /**
     * Reverse the migrations.
     *
     * Note: Only basic structure restoration for rollback.
     * Data cannot be restored without backups.
     */
    public function down(): void
    {
        // Restore test_livekit_session
        Schema::create('test_livekit_session', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            // Basic structure only - original columns not documented
        });

        // Restore academic_progresses
        Schema::create('academic_progresses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            // Basic structure only - was duplicate of academic_progress
        });

        // Restore service_requests
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // Restore Google integration tables
        Schema::create('google_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_google_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('academy_google_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('google_calendar_id')->nullable();
            $table->string('google_meet_enabled')->default(false);
            $table->timestamps();
        });
    }
};
