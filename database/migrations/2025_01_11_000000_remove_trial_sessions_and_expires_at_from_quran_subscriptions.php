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
            $table->dropColumn(['trial_sessions', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->integer('trial_sessions')->default(0)->after('subscription_status');
            $table->datetime('expires_at')->nullable()->after('starts_at');
        });
    }
};
