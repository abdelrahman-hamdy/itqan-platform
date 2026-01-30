<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds cancellation_type column to support smart cancellation logic:
     * - 'teacher' = cancelled by teacher (does NOT count towards subscription)
     * - 'student' = cancelled by student (DOES count towards subscription)
     * - 'system' = cancelled by system (does NOT count towards subscription)
     */
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->string('cancellation_type')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn('cancellation_type');
        });
    }
};
