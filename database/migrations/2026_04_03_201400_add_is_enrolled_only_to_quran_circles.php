<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->boolean('is_enrolled_only')->default(false)->after('allow_sponsored_requests');
        });
    }

    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('is_enrolled_only');
        });
    }
};
