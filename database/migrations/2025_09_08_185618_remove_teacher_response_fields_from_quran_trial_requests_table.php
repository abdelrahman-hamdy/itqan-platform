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
        Schema::table('quran_trial_requests', function (Blueprint $table) {
            // Remove teacher response fields
            $table->dropColumn(['teacher_response', 'responded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_trial_requests', function (Blueprint $table) {
            // Add back the teacher response fields
            $table->text('teacher_response')->nullable();
            $table->timestamp('responded_at')->nullable();
        });
    }
};
