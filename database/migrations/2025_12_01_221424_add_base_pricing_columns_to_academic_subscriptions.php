<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add BaseSubscription pricing columns that are missing from academic_subscriptions.
     */
    public function up(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Pricing columns expected by BaseSubscription model
            if (! Schema::hasColumn('academic_subscriptions', 'monthly_price')) {
                $table->decimal('monthly_price', 10, 2)->nullable()->after('hourly_rate');
            }

            if (! Schema::hasColumn('academic_subscriptions', 'quarterly_price')) {
                $table->decimal('quarterly_price', 10, 2)->nullable()->after('monthly_price');
            }

            if (! Schema::hasColumn('academic_subscriptions', 'yearly_price')) {
                $table->decimal('yearly_price', 10, 2)->nullable()->after('quarterly_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            $columns = ['monthly_price', 'quarterly_price', 'yearly_price'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('academic_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
