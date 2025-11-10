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
        Schema::table('quran_circles', function (Blueprint $table) {
            // Note: quran_circles already has preparation_minutes, ending_buffer_minutes, and late_join_grace_period_minutes
            // We only need to add the attendance_threshold_percentage field
            $table->decimal('attendance_threshold_percentage', 5, 2)->nullable()->after('late_join_grace_period_minutes')
                ->comment('Minimum attendance percentage required (e.g., 80.00)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('attendance_threshold_percentage');
        });
    }
};
