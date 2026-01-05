<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop unused meeting-related columns.
     * These fields belong on sessions, not lessons.
     */
    public function up(): void
    {
        Schema::table('academic_individual_lessons', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_link',
                'meeting_id',
                'meeting_password',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_individual_lessons', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('last_session_at');
            $table->string('meeting_id')->nullable()->after('meeting_link');
            $table->string('meeting_password')->nullable()->after('meeting_id');
        });
    }
};
