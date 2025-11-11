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
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->decimal('session_price_individual', 8, 2)->nullable()->after('total_sessions')->comment('سعر الحصة الفردية');
            $table->decimal('session_price_group', 8, 2)->nullable()->after('session_price_individual')->comment('سعر الحصة الجماعية');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['session_price_individual', 'session_price_group']);
        });
    }
};