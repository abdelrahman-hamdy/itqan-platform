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
            $table->text('lesson_content')->nullable()->after('description')->comment('محتوى الدرس');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropColumn('lesson_content');
        });
    }
};
