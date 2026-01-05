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
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            // Rename notes to admin_notes
            if (Schema::hasColumn('quran_individual_circles', 'notes')) {
                $table->renameColumn('notes', 'admin_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            if (Schema::hasColumn('quran_individual_circles', 'admin_notes')) {
                $table->renameColumn('admin_notes', 'notes');
            }
        });
    }
};
