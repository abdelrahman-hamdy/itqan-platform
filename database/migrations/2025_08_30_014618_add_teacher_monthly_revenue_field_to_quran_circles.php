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
            // Add teacher monthly revenue field
            if (! Schema::hasColumn('quran_circles', 'teacher_monthly_revenue')) {
                $table->decimal('teacher_monthly_revenue', 8, 2)->nullable()->after('monthly_fee')
                    ->comment('Monthly revenue/salary for the teacher from this circle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            if (Schema::hasColumn('quran_circles', 'teacher_monthly_revenue')) {
                $table->dropColumn('teacher_monthly_revenue');
            }
        });
    }
};
