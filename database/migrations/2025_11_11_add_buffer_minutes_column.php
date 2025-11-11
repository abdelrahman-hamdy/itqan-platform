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
        // Add the missing default_buffer_minutes column if it doesn't exist
        Schema::table('academy_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_settings', 'default_buffer_minutes')) {
                $table->unsignedInteger('default_buffer_minutes')->default(5)->after('default_preparation_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->dropColumn('default_buffer_minutes');
        });
    }
};
