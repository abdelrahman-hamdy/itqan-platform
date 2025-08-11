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
            $table->enum('schedule_period', ['week', 'month', 'two_months'])
                  ->default('month')
                  ->after('monthly_sessions_count')
                  ->comment('Period for generating sessions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('schedule_period');
        });
    }
};