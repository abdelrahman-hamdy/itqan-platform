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
        Schema::table('subjects', function (Blueprint $table) {
            // Remove the old fields
            $table->dropColumn(['hours_per_week', 'prerequisites']);
            
            // Add admin notes field
            $table->text('admin_notes')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Remove admin notes field
            $table->dropColumn('admin_notes');
            
            // Add back the old fields
            $table->integer('hours_per_week')->nullable()->after('description');
            $table->text('prerequisites')->nullable()->after('hours_per_week');
        });
    }
};
