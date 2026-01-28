<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds payment_settings JSON column to store per-academy payment gateway configuration.
     * Structure:
     * {
     *   "default_gateway": "easykash",
     *   "enabled_gateways": ["paymob", "easykash"],
     *   "paymob": { "use_global": true },
     *   "easykash": {
     *     "use_global": false,
     *     "api_key": "encrypted_value",
     *     "secret_key": "encrypted_value"
     *   }
     * }
     */
    public function up(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->json('payment_settings')->nullable()->after('notification_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academies', function (Blueprint $table) {
            $table->dropColumn('payment_settings');
        });
    }
};
