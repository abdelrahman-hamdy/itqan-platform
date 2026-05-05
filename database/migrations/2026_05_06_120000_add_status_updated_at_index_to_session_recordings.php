<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX session_recordings_status_updated_at_index '.
            'ON session_recordings (status, updated_at)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX session_recordings_status_updated_at_index ON session_recordings');
    }
};
