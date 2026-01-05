<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify teacher_payouts table by removing payment tracking columns.
 *
 * The payouts system is for confirming/approving earnings only,
 * not for tracking actual payments.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, convert any 'paid' status to 'approved' since we're removing that status
        DB::table('teacher_payouts')
            ->where('status', 'paid')
            ->update(['status' => 'approved']);

        // Remove payment-related columns
        Schema::table('teacher_payouts', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('teacher_payouts', 'paid_by')) {
                $table->dropForeign(['paid_by']);
                $table->dropColumn('paid_by');
            }

            if (Schema::hasColumn('teacher_payouts', 'paid_at')) {
                $table->dropColumn('paid_at');
            }

            if (Schema::hasColumn('teacher_payouts', 'payment_method')) {
                $table->dropColumn('payment_method');
            }

            if (Schema::hasColumn('teacher_payouts', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }

            if (Schema::hasColumn('teacher_payouts', 'payment_notes')) {
                $table->dropColumn('payment_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_payouts', function (Blueprint $table) {
            $table->foreignId('paid_by')->nullable()->after('approval_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->after('paid_by');
            $table->string('payment_method')->nullable()->after('paid_at');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->text('payment_notes')->nullable()->after('payment_reference');
        });
    }
};
