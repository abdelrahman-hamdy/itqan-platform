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
            $table->json('package_ids')->nullable()->after('languages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('package_ids');
        });
    }
};
