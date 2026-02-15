<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove payout_id column from teacher_earnings table.
     * The payout system is being removed as it adds unnecessary complexity
     * without providing value (teachers already see earnings immediately).
     */
    public function up(): void
    {
        Schema::table('teacher_earnings', function (Blueprint $table) {
            // Drop index first (if exists)
            if (Schema::hasColumn('teacher_earnings', 'payout_id')) {
                // Check if index exists before dropping
                $indexExists = DB::select(
                    "SHOW INDEX FROM teacher_earnings WHERE Key_name = 'teacher_earnings_payout_idx'"
                );
                if (! empty($indexExists)) {
                    $table->dropIndex('teacher_earnings_payout_idx');
                }
            }

            // Drop the column (no foreign key constraint exists)
            $table->dropColumn('payout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_earnings', function (Blueprint $table) {
            // Restore the payout_id column
            $table->unsignedBigInteger('payout_id')->nullable()->after('calculated_at');

            // Restore index (no foreign key constraint existed)
            $table->index('payout_id', 'teacher_earnings_payout_idx');
        });
    }
};
