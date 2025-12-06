<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add relationship_type back to parent_profiles with only 3 options
        DB::statement("ALTER TABLE parent_profiles ADD relationship_type ENUM('father', 'mother', 'other') DEFAULT NULL AFTER occupation");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_profiles', function (Blueprint $table) {
            $table->dropColumn('relationship_type');
        });
    }
};
