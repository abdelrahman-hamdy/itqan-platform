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
        Schema::table('academy_google_settings', function (Blueprint $table) {
            // Change google_client_secret from text to longText to handle larger encrypted strings
            $table->longText('google_client_secret')->nullable()->change();
            
            // Also update google_service_account_key and fallback_account_credentials for consistency
            $table->longText('google_service_account_key')->nullable()->change();
            $table->longText('fallback_account_credentials')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_google_settings', function (Blueprint $table) {
            // Revert back to text
            $table->text('google_client_secret')->nullable()->change();
            $table->text('google_service_account_key')->nullable()->change();
            $table->text('fallback_account_credentials')->nullable()->change();
        });
    }
};