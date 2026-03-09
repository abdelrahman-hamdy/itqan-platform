<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->unsignedInteger('session_number')->nullable()->after('monthly_session_number');
            $table->index(['individual_circle_id', 'session_number']);
            $table->index(['circle_id', 'session_number']);
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->unsignedInteger('session_number')->nullable()->after('session_code');
            $table->index(['academic_subscription_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropIndex(['individual_circle_id', 'session_number']);
            $table->dropIndex(['circle_id', 'session_number']);
            $table->dropColumn('session_number');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndex(['academic_subscription_id', 'session_number']);
            $table->dropColumn('session_number');
        });
    }
};
