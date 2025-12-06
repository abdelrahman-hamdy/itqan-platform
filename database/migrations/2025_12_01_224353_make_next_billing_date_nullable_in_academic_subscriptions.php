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
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            // Make next_billing_date nullable (it may not be set initially)
            if (Schema::hasColumn('academic_subscriptions', 'next_billing_date')) {
                $table->date('next_billing_date')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('academic_subscriptions', 'next_billing_date')) {
                $table->date('next_billing_date')->nullable(false)->change();
            }
        });
    }
};
