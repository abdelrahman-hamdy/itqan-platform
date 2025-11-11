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
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            $table->decimal('papers_memorized_today', 5, 2)->nullable()->comment('عدد الأوجه المحفوظة اليوم');
            $table->integer('verses_memorized_today')->nullable()->comment('عدد الآيات المحفوظة اليوم');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_session_attendances', function (Blueprint $table) {
            $table->dropColumn(['papers_memorized_today', 'verses_memorized_today']);
        });
    }
};
