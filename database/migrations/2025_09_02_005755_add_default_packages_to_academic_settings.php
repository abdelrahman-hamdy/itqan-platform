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
        Schema::table('academic_settings', function (Blueprint $table) {
            $table->json('default_package_ids')->nullable()->after('available_languages')
                ->comment('JSON array of default academic package IDs for new teachers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_settings', function (Blueprint $table) {
            $table->dropColumn('default_package_ids');
        });
    }
};