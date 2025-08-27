<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear existing problematic records
        DB::table('media')->truncate();

        // Ensure proper charset and collation
        DB::statement('ALTER TABLE media CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Modify specific columns to ensure they can handle all characters
        Schema::table('media', function (Blueprint $table) {
            $table->string('name', 500)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->string('file_name', 500)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('file_name', 255)->change();
        });
    }
};
