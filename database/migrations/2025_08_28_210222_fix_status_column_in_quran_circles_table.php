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
        // First, add a temporary boolean column
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->boolean('status_new')->default(true)->after('status');
        });

        // Update the new column based on old status values
        DB::table('quran_circles')->whereIn('status', ['active', 'ongoing'])->update(['status_new' => true]);
        DB::table('quran_circles')->whereNotIn('status', ['active', 'ongoing'])->update(['status_new' => false]);

        // Drop the old status column
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Rename the new column to status
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old status column as enum
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->enum('status_old', ['active', 'paused', 'cancelled', 'completed'])->default('active')->after('status');
        });

        // Convert boolean back to enum values
        DB::table('quran_circles')->where('status', true)->update(['status_old' => 'active']);
        DB::table('quran_circles')->where('status', false)->update(['status_old' => 'paused']);

        // Drop the boolean column
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Rename the enum column back to status
        Schema::table('quran_circles', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });
    }
};
