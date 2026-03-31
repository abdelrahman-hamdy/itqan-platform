<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->boolean('recording_enabled')->default(false)->after('is_active')
                ->comment('Enable audio-only recording for sessions in this circle');
        });
    }

    public function down(): void
    {
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropColumn('recording_enabled');
        });
    }
};
