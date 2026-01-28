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
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('quran_subscriptions', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('total_price');
            }
            if (! Schema::hasColumn('quran_subscriptions', 'final_price')) {
                $table->decimal('final_price', 10, 2)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('quran_subscriptions', 'progress_percentage')) {
                $table->decimal('progress_percentage', 5, 2)->default(0)->after('sessions_remaining');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'final_price', 'progress_percentage']);
        });
    }
};
