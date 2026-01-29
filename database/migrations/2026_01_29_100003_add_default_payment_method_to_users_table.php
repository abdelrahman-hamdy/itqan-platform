<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a reference to the user's default payment method for
     * quick access during checkout and auto-renewal.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_payment_method_id')
                ->nullable()
                ->after('email')
                ->constrained('saved_payment_methods')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_payment_method_id']);
            $table->dropColumn('default_payment_method_id');
        });
    }
};
