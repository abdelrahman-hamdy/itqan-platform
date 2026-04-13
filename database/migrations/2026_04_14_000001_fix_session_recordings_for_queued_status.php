<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'queued' and 'skipped' to status enum
        DB::statement("ALTER TABLE session_recordings MODIFY COLUMN status ENUM('queued','recording','processing','completed','failed','skipped','deleted') NOT NULL DEFAULT 'recording'");

        // Make recording_id nullable — queued records don't have an egress ID yet
        DB::statement('ALTER TABLE session_recordings MODIFY COLUMN recording_id VARCHAR(255) NULL');

        // Make started_at nullable — queued records haven't started
        DB::statement('ALTER TABLE session_recordings MODIFY COLUMN started_at TIMESTAMP NULL');

        // Drop unique constraint on recording_id (queued records have NULL)
        // Use raw SQL to avoid issues with Laravel's index name conventions
        try {
            DB::statement('ALTER TABLE session_recordings DROP INDEX session_recordings_recording_id_unique');
        } catch (\Exception $e) {
            // Index may not exist or already dropped
        }

        // Add indexes for queue operations (idempotent)
        try {
            DB::statement('CREATE INDEX session_recordings_recording_id_index ON session_recordings (recording_id)');
        } catch (\Exception $e) {
            // Index may already exist
        }
        try {
            DB::statement('CREATE INDEX session_recordings_status_queued_at_index ON session_recordings (status, queued_at)');
        } catch (\Exception $e) {
        }
        try {
            DB::statement('CREATE INDEX session_recordings_recordable_status_index ON session_recordings (recordable_type, recordable_id, status)');
        } catch (\Exception $e) {
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE session_recordings MODIFY COLUMN status ENUM('recording','processing','completed','failed','deleted') NOT NULL DEFAULT 'recording'");
        DB::statement('ALTER TABLE session_recordings MODIFY COLUMN recording_id VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE session_recordings MODIFY COLUMN started_at TIMESTAMP NOT NULL');

        Schema::table('session_recordings', function ($table) {
            $table->dropIndex(['status', 'queued_at']);
            $table->dropIndex(['recordable_type', 'recordable_id', 'status']);
            $table->dropIndex(['recording_id']);
            $table->unique('recording_id');
        });
    }
};
