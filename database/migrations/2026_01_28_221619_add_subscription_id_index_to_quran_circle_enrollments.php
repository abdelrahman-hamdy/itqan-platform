<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds index on subscription_id for faster lookup when querying
     * enrollments by their linked subscription.
     */
    public function up(): void
    {
        Schema::table('quran_circle_enrollments', function (Blueprint $table) {
            $table->index('subscription_id', 'qce_subscription_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circle_enrollments', function (Blueprint $table) {
            $table->dropIndex('qce_subscription_id_index');
        });
    }
};
