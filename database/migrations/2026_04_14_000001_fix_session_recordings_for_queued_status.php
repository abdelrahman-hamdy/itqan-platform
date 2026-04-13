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

        // Drop unique index on recording_id (queued records have NULL recording_id)
        Schema::table('session_recordings', function ($table) {
            $table->dropUnique(['recording_id']);
            $table->index('recording_id');
        });

        // Add indexes for queue operations
        Schema::table('session_recordings', function ($table) {
            $table->index(['status', 'queued_at']);
            $table->index(['recordable_type', 'recordable_id', 'status']);
        });
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
