<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('trial_request_id')->nullable()->after('student_id');
            $table->foreign('trial_request_id')->references('id')->on('quran_trial_requests')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropForeign(['trial_request_id']);
            $table->dropColumn('trial_request_id');
        });
    }
};
